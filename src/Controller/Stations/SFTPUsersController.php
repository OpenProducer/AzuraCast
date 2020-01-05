<?php
namespace App\Controller\Stations;

use App\Exception\StationUnsupportedException;
use App\Form\SFTPUserForm;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\AzuraCastCentral;
use App\Service\SFTPGo;
use Azura\Session\Flash;
use Psr\Http\Message\ResponseInterface;

class SFTPUsersController extends AbstractStationCrudController
{
    protected AzuraCastCentral $ac_central;

    public function __construct(SFTPUserForm $form, AzuraCastCentral $ac_central)
    {
        parent::__construct($form);

        $this->ac_central = $ac_central;
        $this->csrf_namespace = 'stations_sftp_users';
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        if (!SFTPGo::isSupported()) {
            throw new StationUnsupportedException(__('This feature is not currently supported on this station.'));
        }

        $station = $request->getStation();

        $baseUrl = $request->getRouter()->getBaseUrl(false)
            ->withScheme('sftp')
            ->withPort(null);
        $port = $_ENV['AZURACAST_SFTP_PORT'] ?? 2022;

        $sftpInfo = [
            'url' => (string)$baseUrl,
            'ip' => $this->ac_central->getIp(),
            'port' => $port,
        ];

        return $request->getView()->renderToResponse($response, 'stations/sftp_users/index', [
            'users' => $station->getSFTPUsers(),
            'sftp_info' => $sftpInfo,
            'csrf' => $request->getCsrf()->generate($this->csrf_namespace),
        ]);
    }

    public function editAction(ServerRequest $request, Response $response, $id = null): ResponseInterface
    {
        if (false !== $this->_doEdit($request, $id)) {
            $request->getFlash()->addMessage('<b>' . __('Changes saved.') . '</b>', Flash::SUCCESS);
            return $response->withRedirect($request->getRouter()->fromHere('stations:sftp_users:index'));
        }

        return $request->getView()->renderToResponse($response, 'system/form_page', [
            'form' => $this->form,
            'render_mode' => 'edit',
            'title' => $id ? __('Edit SFTP User') : __('Add SFTP User'),
        ]);
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        $id,
        $csrf
    ): ResponseInterface {
        $this->_doDelete($request, $id, $csrf);

        $request->getFlash()->addMessage('<b>' . __('SFTP User deleted.') . '</b>', Flash::SUCCESS);
        return $response->withRedirect($request->getRouter()->fromHere('stations:sftp_users:index'));
    }
}
