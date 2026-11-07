<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Libraries\RbacService;

class RbacFilter implements FilterInterface
{
    /**
     * Inspect incoming request and enforce RBAC permission arguments.
     * Example route usage: ['filter' => 'rbac:matching']
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        if (!$session->get('admin_valid') || !$session->get('admin_id')) {
            return redirect()->to('admin/login');
        }

        if (!empty($arguments)) {
            $permission = $arguments[0];
            if (!RbacService::can($permission, $session)) {
                if ($request->isAJAX()) {
                    return service('response')->setJSON([
                        'status' => 'error',
                        'message' => 'Akses ditolak: Anda tidak memiliki izin untuk aksi ini.'
                    ])->setStatusCode(403);
                }

                $session->setFlashdata('k', '<div class="alert alert-danger" id="alert"><i class="bi bi-shield-x me-2"></i>Akses ditolak: Anda tidak memiliki izin untuk mengakses menu tersebut.</div>');
                return redirect()->to('admin');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No action required after request
    }
}

