<?php
namespace App\Controller\Admin;

use App\Form\Form;
use App\Http\Request;
use App\Http\Response;
use App\Radio\Frontend\SHOUTcast;
use Psr\Http\Message\ResponseInterface;
use Slim\Http\UploadedFile;
use Symfony\Component\Process\Process;

class InstallShoutcastController
{
    /** @var array */
    protected $form_config;

    /**
     * @param array $form_config
     * @see \App\Provider\AdminProvider
     */
    public function __construct(array $form_config)
    {
        $this->form_config = $form_config;
    }

    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        $form_config = $this->form_config;

        $version = SHOUTcast::getVersion();

        if (null !== $version) {
            $form_config['groups'][0]['elements']['current_version'][1]['markup'] = '<p class="text-success">'.__('SHOUTcast version "%s" is currently installed.', $version).'</p>';
        }

        $form = new Form($form_config, []);

        if ($request->isPost() && $form->isValid($_POST)) {
            try
            {
                $sc_base_dir = dirname(APP_INCLUDE_ROOT) . '/servers/shoutcast2';

                $files = $request->getUploadedFiles();
                /** @var UploadedFile $import_file */
                $import_file = $files['binary'];

                if ($import_file->getError() === \UPLOAD_ERR_OK) {
                    $sc_tgz_path = $sc_base_dir.'/sc_serv.tar.gz';
                    if (file_exists($sc_tgz_path)) {
                        unlink($sc_tgz_path);
                    }

                    $import_file->moveTo($sc_tgz_path);

                    $process = new Process([
                        'tar',
                        'xvzf',
                        $sc_tgz_path
                    ], $sc_base_dir);

                    $process->mustRun();
                }

                return $response->withRedirect($request->getUri()->getPath());
            } catch(\Exception $e) {
                $form
                    ->getField('binary')
                    ->addError(get_class($e).': '.$e->getMessage());
            }
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $form,
            'render_mode' => 'edit',
            'title' => __('Install SHOUTcast'),
        ]);
    }
}
