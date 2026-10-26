<?php

namespace App\Controllers;

use CodeIgniter\Exceptions\PageNotFoundException;

class Mirror extends BaseController
{
    public function contactCompany()
    {
        return $this->index('ติดต่อเรา/6a585ad0a89624001409825f');
    }

    public function index(string $path = '')
    {
        $manifestPath = WRITEPATH . 'mirror/manifest.json';
        if (!is_file($manifestPath)) {
            throw PageNotFoundException::forPageNotFound('The local website snapshot is still being prepared.');
        }

        $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $requested = '/' . trim(rawurldecode($path), '/');
        foreach ($manifest['pages'] as $page) {
            if (rtrim(rawurldecode($page['path']), '/') !== rtrim($requested, '/')) {
                continue;
            }

            $html = file_get_contents(WRITEPATH . 'mirror/pages/' . basename($page['file']));
            $html = str_replace(['__LOCAL_BASE__/', '__LOCAL_BASE__'], base_url(), $html);
            $html = \App\Libraries\Presentation::apply($html, $requested);
            $widget = '<meta name="platform-base" content="' . esc(base_url(), 'attr') . '"><meta name="platform-mirror" content="1"><script src="' . base_url('platform/widget.js') . '" defer></script>';
            $html = str_replace('</head>', $widget . '</head>', $html);

            return $this->response->setHeader('Cache-Control','private, no-store')->setContentType('text/html', 'UTF-8')->setBody($html);
        }

        throw PageNotFoundException::forPageNotFound('This URL was not available in the public website snapshot.');
    }
}
