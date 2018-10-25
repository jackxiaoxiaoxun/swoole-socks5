<?php

namespace ss5;


class socks5
{

    const  STAGE_INIT= 0;
    const  STAGE_AUTH= 1;
    const  STAGE_ADDR= 2;
    const  STAGE_UDP_ASSOC= 3;
    const  STAGE_DNS= 4;
    const  STAGE_CONNECTING= 5;
    const  STAGE_STREAM= 6;
    const  STAGE_DESTROYED= -1;

    const  CMD_CONNECT= 1;
    const  CMD_BIND= 2;
    const  CMD_UDP_ASSOCIATE= 3;


    const  ADDRTYPE_IPV4= 1;
    const  ADDRTYPE_IPV6= 4;
    const  ADDRTYPE_HOST= 3;

    const  METHOD_NO_AUTH= 0;
    const  METHOD_GSSAPI= 1;
    const  METHOD_USER_PASS= 2;

    const  REPLY_INIT   = "\x05\x00";
    const  REPLY_ADDR    = "\x05\x00\x00\x01\x00\x00\x00\x00\x10\x10";

    public static function parse_socket5_header($buffer)
    {
        $addr_type = ord($buffer[3]);
        switch($addr_type)
        {
            case self::ADDRTYPE_IPV4:
                if(strlen($buffer) < 10)
                {
                    echo bin2hex($buffer)."\n";
                    echo "buffer too short\n";
                    return false;
                }
                $dest_addr = ord($buffer[4]).'.'.ord($buffer[5]).'.'.ord($buffer[6]).'.'.ord($buffer[7]);
                $port_data = unpack('n', substr($buffer, -2));
                $dest_port = $port_data[1];
                $header_length = 10;
                break;
            case self::ADDRTYPE_HOST:
                $addrlen = ord($buffer[4]);
                if(strlen($buffer) < $addrlen + 5)
                {
                    echo $buffer."\n";
                    echo bin2hex($buffer)."\n";
                    echo "buffer too short\n";
                    return false;
                }
                $dest_addr = substr($buffer, 5, $addrlen);
                $port_data = unpack('n', substr($buffer, -2));
                $dest_port = $port_data[1];
                $header_length = $addrlen + 7;
                break;
            case self::ADDRTYPE_IPV6:
                if(strlen($buffer) < 22)
                {
                    echo "buffer too short\n";
                    return false;
                }
                echo "todo ipv6\n";
                return false;
            default:
                echo "unsupported addrtype $addr_type\n";
                return false;
        }
        return array($addr_type, $dest_addr, $dest_port, $header_length);
    }


}




