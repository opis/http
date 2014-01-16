<?php

namespace Opis\Http;

class ProxyHandler
{
    protected $proxies = array();
    
    protected $hosts  = array();
    
    protected $headers = array(
        'ip' => 'X_FORWARDED_FOR',
        'host' => 'X_FORWARDED_HOST',
        'proto' => 'X_FORWARDED_PROTO',
        'port' => 'X_FORWARDED_PORT',
    );
    
    protected $trustedHosts = array();
    
    public function addProxy($name)
    {
        if(!is_array($name))
        {
            $name = array($name);
        }
        $this->proxies = array_merge($name, $this->proxies);
        return $this;
    }
     
    public function addHost($pattern, array $placeholders = array())
    {
        if(!is_array($pattern))
        {
            $pattern = array($pattern);
        }
        
        foreach($pattern as $host)
        {
            $host = str_replace('.', '\.', $host);
            $host = str_replace('*', '.*', $host);
            foreach($placeholders as $key => $value)
            {
                $host = str_replace('{' . $key . '}', $value, $value);
            }
            $this->hosts[] = $host;
        }
        
        return $this;
    }
    
    public function isTrustedHost($host)
    {
        if(count($this->hosts) > 0)
        {
            if(in_array($host, $this->trustedHosts))
            {
                return true;
            }
            foreach($this->hosts as $pattern)
            {
                if(preg_match($pattern, $host))
                {
                    $this->trustedHosts[] = $host;
                    return true;
                }
            }
            return false;
        }
        
        return true;
    }
    
    protected function setHeaderName($key, $value)
    {
        $this->headers[$key] = $value;
    }
    
    protected function getHeader($key, Request $request, $default)
    {
        return $this->headers[$key] ? $request->header($this->headers[$key], $default) : $default;
    }
    
    public function ipHeaderName($name)
    {
        return $this->setHeaderName('ip', $value);
    }
    
    public function hostHeaderName($name)
    {
        return $this->setHeaderName('host', $name);
    }
    
    public function protoHeaderName($name)
    {
        return $this->setHeaderName('proto', $name);
    }
    
    public function portHeaderName($name)
    {
        return $this->setHeaderName('port', $name);
    }
    
    public function getIp(Request $request, $default = null)
    {
        return $this->getHeader('ip', $request, $default);
    }
    
    public function getHost(Request $request, $default = null)
    {
        return $this->getHeader('host', $request, $default);
    }
    
    public function getProto(Request $request, $default = null)
    {
        return $this->getHeader('proto', $request, $default);
    }
    
    public function getPort(Request $request, $default = null)
    {
        return $this->getHeader('port', $request, $default);
    }
    
    public function getProxies()
    {
        return $this->proxies;
    }
    
    public function getHosts()
    {
        return $this->hosts;
    }
    
    public function getHeaders()
    {
        return $this->headers;
    }
    
    /**
     * Checks if an IPv4 or IPv6 address is contained in the list of given IPs or subnets
     *
     * @author Fabien Potencier <fabien@symfony.com>
     *
     * @param string       $requestIp   IP to check
     * @param string|array $ips         List of IPs or subnets (can be a string if only a single one)
     *
     * @return boolean Whether the IP is valid
     */
    
    public function checkIp($requestIp, $ips)
    {
        if (!is_array($ips))
        {
            $ips = array($ips);
        }
        
        $method = false !== strpos($requestIp, ':') ? 'checkIp6': 'checkIp4';
        
        foreach ($ips as $ip)
        {
            if ($this->$method($requestIp, $ip))
            {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Compares two IPv4 addresses.
     * In case a subnet is given, it checks if it contains the request IP.
     *
     * @author Fabien Potencier <fabien@symfony.com>
     *
     * @param string $requestIp IPv4 address to check
     * @param string $ip        IPv4 address or subnet in CIDR notation
     *
     * @return boolean Whether the IP is valid
     */
    
    public function checkIp4($requestIp, $ip)
    {
        if (false !== strpos($ip, '/'))
        {
            list($address, $netmask) = explode('/', $ip, 2);
            
            if ($netmask < 1 || $netmask > 32)
            {
                return false;
            }
        }
        else
        {
            $address = $ip;
            $netmask = 32;
        }
        return 0 === substr_compare(sprintf('%032b', ip2long($requestIp)), sprintf('%032b', ip2long($address)), 0, $netmask);
    }
    
    /**
     * Compares two IPv6 addresses.
     * In case a subnet is given, it checks if it contains the request IP.
     *
     * @author David Soria Parra <dsp at php dot net>
     * @see https://github.com/dsp/v6tools
     *
     * @param string $requestIp IPv6 address to check
     * @param string $ip        IPv6 address or subnet in CIDR notation
     *
     * @return boolean Whether the IP is valid
     *
     * @throws \RuntimeException When IPV6 support is not enabled
     */
    
    public function checkIp6($requestIp, $ip)
    {
        if (!((extension_loaded('sockets') && defined('AF_INET6')) || @inet_pton('::1')))
        {
            throw new \RuntimeException('Unable to check Ipv6. Check that PHP was not compiled with option "disable-ipv6".');
        }
        
        if (false !== strpos($ip, '/'))
        {
            list($address, $netmask) = explode('/', $ip, 2);
            
            if ($netmask < 1 || $netmask > 128)
            {
                return false;
            }
        }
        else
        {
            $address = $ip;
            $netmask = 128;
        }
        
        $bytesAddr = unpack("n*", inet_pton($address));
        $bytesTest = unpack("n*", inet_pton($requestIp));
        
        for ($i = 1, $ceil = ceil($netmask / 16); $i <= $ceil; $i++)
        {
            $left = $netmask - 16 * ($i-1);
            $left = ($left <= 16) ? $left : 16;
            $mask = ~(0xffff >> $left) & 0xffff;
            if (($bytesAddr[$i] & $mask) != ($bytesTest[$i] & $mask))
            {
                return false;
            }
        }
        
        return true;
    }
}
