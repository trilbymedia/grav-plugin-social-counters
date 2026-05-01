<?php
namespace Grav\Plugin;

use Grav\Common\HTTP\Client;
use Grav\Common\Plugin;
use RocketTheme\Toolbox\Event\Event;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class SocialCountersPlugin
 * @package Grav\Plugin
 */
class SocialCountersPlugin extends Plugin
{
    protected $active = false;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Initialize configuration
     */
    public function onPluginsInitialized()
    {
        if ($this->isAdmin()) {
            $this->active = false;
            return;
        }

        $this->enable([
            'onTwigSiteVariables' => ['onTwigSiteVariables', 0]
        ]);
    }


    /**
     * Make form accessible from twig.
     */
    public function onTwigSiteVariables()
    {
        require_once $this->grav['locator']->findResource('plugins://github/vendor/autoload.php');

        $config = $this->config->get('plugins.social-counters');

        $cache = $this->grav['cache'];
        $cache_id = md5('social-counters'.$cache->getKey());

        $github = $cache->fetch($cache_id . '-github');
        $twitter = $cache->fetch($cache_id . '-twitter');

        // Github not found in cache, try again
        if ($github === false) {

            $client = new \Github\Client();

            $repo = $client->api('repo');

            try {
                $github['stars'] = $repo->show($config['github']['user'], $config['github']['repo'])['stargazers_count'];
                $cache->save($cache_id . '-github', $github, $config['cache_timeout']);
            } catch(\Exception $e) {
                $github['error'] = $e->getMessage();
            }
        }

        // Twitter not found in cache, try again
        if ($twitter === false) {

            $followers = null;

            $client = Client::getClient();

            try {
//                $response = $client->request('GET', 'https://livecounts.io/twitter-live-follower-counter/' . $config['twitter']['user']);
//                $status = $response->getStatusCode();
//                if ($status === 200) {
//                    $body = $response->getContent();
//
//                    $crawler = new Crawler($body);
//                    $follower_text = $crawler->filterXpath('//div[@class="odometer"]')->text();
//                    $followers = intval($follower_text);
//                }
                $followers = 5772;

                if (is_int($followers)) {
                    $twitter['followers'] = $followers;
                    $cache->save($cache_id . '-twitter', $twitter, $config['cache_timeout']);
                } else {
                    $twitter['error'] = 'Could not retrieve Twitter followers';
                }
            } catch (\Exception $e) {
                $twitter['error'] = $e->getMessage();
            }
        }

        $this->grav['twig']->twig_vars['social_counters'] = ['github' => $github, 'twitter' => $twitter];

    }
}
