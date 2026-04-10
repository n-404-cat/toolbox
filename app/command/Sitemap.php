<?php
declare (strict_types = 1);

namespace app\command;

use app\model\Plugin;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

class Sitemap extends Command
{
    protected function configure()
    {
        $this->setName('sitemap:generate')
            ->addOption('domain', 'd', Option::VALUE_OPTIONAL, 'The domain of the website (e.g. https://tool.phpers.xyz)', '')
            ->setDescription('Generate sitemap.xml for the user side');
    }

    protected function execute(Input $input, Output $output)
    {
        $domain = $input->getOption('domain');
        if (empty($domain)) {
            $domain = env('app.host', '');
            if (empty($domain)) {
                $domain = config_get('global.site_url', '');
            }
            if (empty($domain)) {
                try {
                    $domain = request()->domain();
                } catch (\Exception $e) {
                    $domain = 'http://localhost';
                }
            }
        }
        
        $domain = rtrim($domain, '/');

        $sitemapPath = public_path() . 'sitemap.xml';

        $xml = new \XMLWriter();
        $xml->openURI($sitemapPath);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->setIndent(true);
        
        $xml->startElement('urlset');
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        // Home page
        $this->addUrl($xml, $domain . '/', date('Y-m-d'), 'daily', '1.0');

        // Plugins
        $plugins = Plugin::where('enable', 1)->select();
        foreach ($plugins as $plugin) {
            $updateTime = date('Y-m-d', strtotime($plugin->update_time ?: ($plugin->create_time ?: date('Y-m-d H:i:s'))));
            $this->addUrl($xml, $domain . '/' . $plugin->alias, $updateTime, 'weekly', '0.8');
        }

        $xml->endElement(); // urlset
        $xml->endDocument();
        $xml->flush();

        $output->writeln("Sitemap generated successfully at: " . $sitemapPath);
    }

    private function addUrl(\XMLWriter $xml, $loc, $lastmod, $changefreq, $priority)
    {
        $xml->startElement('url');
        $xml->writeElement('loc', $loc);
        $xml->writeElement('lastmod', $lastmod);
        $xml->writeElement('changefreq', $changefreq);
        $xml->writeElement('priority', $priority);
        $xml->endElement();
    }
}
