<?php

namespace ss5;


class ss5server
{
    private $server;

    private $host;
    private $port;

    private $clients;

    public function __construct($host, $port)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function init_server()
    {
        $this->server = new \swoole_server($this->host, $this->port);

        $this->server->on('connect', [$this,"on_connect"]);
        $this->server->on('receive', [$this,"on_receive"]);
        $this->server->on('close', [$this,"on_close"]);

        $this->server->start();

    }

    public function run()
    {
        $this->init_server();
    }

    public function on_connect($input, $fd)
    {
        $this->clients[$fd] = new \stdclass();
        $this->clients[$fd]->stage  = socks5::STAGE_INIT;
        $this->clients[$fd]->data   = "";
    }

    public function on_receive($input, $fd, $re_id, $data)
    {
        $client = $this->clients[$fd];
        $client->data   .= $data;

        switch($client->stage)
        {
            case socks5::STAGE_INIT:
                $input->send($fd,socks5::REPLY_INIT);
                $client->stage  = socks5::STAGE_ADDR;
                $client->data   = '';
            return;
            case socks5::STAGE_ADDR:
                $cmd    = ord($client->data[1]);
                if($cmd != socks5::CMD_CONNECT)
                {
                    echo "bad cmd $cmd\n";
                    $input->close($fd);
                    return;
                }
                $header_data    = socks5::parse_socket5_header($client->data);
                if (! $header_data) 
                {
                    echo "error header_data \n";
                    $input->close($fd);
                    return;
                }
                $client->data = substr($client->data, $header_data[3]);
                $client->stage  = socks5::STAGE_CONNECTING;
                $client->output = new \swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

                $client->output->on('connect', function($output) use($client, $input, $fd, $header_data)
                {
                    $client->stage  = socks5::STAGE_STREAM;
                    $input->send($fd, socks5::REPLY_ADDR);
                    if(strlen($client->data))
                    {
                        echo "remain $fd \n";
                        $output->send($client->data );
                        $client->data   = '';
                    }

                });
                $client->output->on('receive', function($output, $data) use($client, $fd, $input)
                {
                    echo "receive $fd\n";
                    $input->send($fd, $data);
                });

                $client->output->on('error', function($output) use($input, $fd)
                {
                    echo "output error $fd \n";
                    $input->close($fd);
                });
                $client->output->on('close', function($output)
                use($input, $fd)
                {
                    echo "output close $fd \n";
                    $input->close($fd);
                });
                $client->output->connect($header_data[1], $header_data[2],10);


            return;
            case socks5::STAGE_STREAM:
                    echo "output send $fd \n";
                $client->output->send($client->data);
                $client->data   = '';
            return;

        }
    }

    public function on_close($input, $fd)
    {
        echo "input close $fd \n";
        unset($this->clients[$fd]);
    }


}

