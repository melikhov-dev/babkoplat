<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Antoxa
 * Date: 29.05.13
 * Time: 14:39
 */


namespace Flint\Provider;

use Geocoder\Geocoder;
use Geocoder\HttpAdapter\BuzzHttpAdapter;
use Geocoder\Provider\FreeGeoIpProvider;
use Geocoder\Provider\GeoipProvider;
use Geocoder\Provider\IpGeoBaseProvider;
use Geocoder\Provider\MaxMindBinaryProvider;
use Silex\ServiceProviderInterface;
use Silex\Application;


class GeoIpServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['geoIp'] = $app->share(
            function ($app) {
                include( VENDOR . 'maxromanovsky/php-maxmind-geoip/geoipcity.inc');
                $geocoder = new Geocoder();
                $adapter  = new BuzzHttpAdapter();
                $geocoder->registerProviders(array(
                   // new MaxMindBinaryProvider(APPPATH . "/data/GeoLiteCity.dat", GEOIP_MEMORY_CACHE),
                    new IpGeoBaseProvider($adapter)
                ));
                $ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '91.219.65.252';
                $geocode = $geocoder->geocode($ip);
                return $geocode;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}


