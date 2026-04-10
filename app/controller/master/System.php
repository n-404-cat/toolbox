<?php

namespace app\controller\master;


use app\BaseController;
use app\model\Config;
use think\facade\Db;
use think\facade\Request;
use think\helper\Str;

class System extends BaseController
{
    public function all()
    {
        $all = Config::all();
        return success($all);
    }

    public function get()
    {
        $key = Request::param('key');
        $all = config_get($key);
        return success($all);
    }

    public function update()
    {

        $params = Request::param();

        foreach ($params as $v) {
            if (empty($v['key'])) {
                continue;
            }
            $model = \app\model\Config::getByKey($v['key']);
            $model->data([
                'key' => $v['key'],
                'value' => $v['value'],
            ])->save();
        }
        $all = Config::all();
        return success($all);
    }

    public function info()
    {

        $tmp = 'version()';
        $mysqlVersion = Db::query("select version()")[0][$tmp];
        $data = [
            'app_name' => base64_decode('5YKy5pif5bel5YW3566x'),
            'author' => base64_decode('UGx1dG8='),
            'version' => get_version(),
            'framework_version' => app()::VERSION,
            'php_version' => PHP_VERSION,
            'mysql_version' => $mysqlVersion,
            'os' => php_uname(),
            'host' => GetHostByName(env('SERVER_NAME')),
            'date' => date("Y-m-d H:i:s"),
        ];
        return success($data);
    }

    public function templates()
    {
        $glob = glob(root_path() . config("view.view_dir_name") . '/index/*');
        $arr = [];
        foreach ($glob as $v) {
            if (is_dir($v)) {
                array_push($arr, basename($v));
            }
        }
        return success($arr);
    }

    public function plugin_templates()
    {
        $glob = glob(template_path_get() . '/template/*');
        $arr = [
            'default',
        ];
        foreach ($glob as $v) {
            if (is_file($v)) {
                array_push($arr, basename($v, '.html'));
            }
        }
        return success($arr);
    }

    public function permissions()
    {
        $glob = glob(app_path() . '/lib/permission/impl/*');
        $arr = [];
        foreach ($glob as $v) {
            if (is_file($v)) {
                array_push($arr, Str::snake(basename($v, '.php')));
            }
        }
        return success($arr);
    }

    public function sitemap()
    {
        try {
            $domain = env('app.host', '');
            if (empty($domain)) {
                $domain = config_get('global.site_url', '');
            }
            if (empty($domain)) {
                $domain = request()->domain();
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
            $xml->startElement('url');
            $xml->writeElement('loc', $domain . '/');
            $xml->writeElement('lastmod', date('Y-m-d'));
            $xml->writeElement('changefreq', 'daily');
            $xml->writeElement('priority', '1.0');
            $xml->endElement();

            // Plugins
            $plugins = \app\model\Plugin::where('enable', 1)->select();
            foreach ($plugins as $plugin) {
                $updateTime = date('Y-m-d', strtotime($plugin->update_time ?: ($plugin->create_time ?: date('Y-m-d H:i:s'))));
                
                $xml->startElement('url');
                $xml->writeElement('loc', $domain . '/' . $plugin->alias);
                $xml->writeElement('lastmod', $updateTime);
                $xml->writeElement('changefreq', 'weekly');
                $xml->writeElement('priority', '0.8');
                $xml->endElement();
            }

            $xml->endElement(); // urlset
            $xml->endDocument();
            $xml->flush();

            return success(['message' => 'Sitemap generated successfully at ' . $sitemapPath]);
        } catch (\Exception $e) {
            return error($e->getMessage());
        }
    }
}
