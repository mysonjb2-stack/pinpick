<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $urls = [
            ['loc' => 'https://mypinpick.net', 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => 'https://mypinpick.net/explore', 'changefreq' => 'daily', 'priority' => '0.8'],
            ['loc' => 'https://mypinpick.net/terms', 'changefreq' => 'monthly', 'priority' => '0.3'],
            ['loc' => 'https://mypinpick.net/faq', 'changefreq' => 'monthly', 'priority' => '0.3'],
            ['loc' => 'https://mypinpick.net/notices', 'changefreq' => 'weekly', 'priority' => '0.4'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $lastmod = now()->toDateString();

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$url['loc']}</loc>\n";
            $xml .= "    <lastmod>{$lastmod}</lastmod>\n";
            $xml .= "    <changefreq>{$url['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$url['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
