<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouterInterface;

final class DashController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(): Response { return $this->render('dash/index.html.twig'); }

    #[Route('/productos', name: 'app_productos')]
    public function productos(): Response { return $this->render('productos/index.html.twig'); }

    #[Route('/inventario', name: 'app_inventario')]
    public function inventario(): Response { return $this->render('inventario/index.html.twig'); }

    #[Route('/datos-empresa', name: 'app_datos_empresa')]
    public function datosEmpresa(): Response { return $this->render('empresa/datos.html.twig'); }

    #[Route('/usuarios', name: 'app_usuarios')]
    public function usuarios(): Response { return $this->render('usuarios/index.html.twig'); }

    #[Route('/grupos', name: 'app_grupos')]
    public function grupos(): Response { return $this->render('usuarios/grupos.html.twig'); }

    #[Route('/ventas', name: 'app_ventas')]
    public function ventas(): Response { return $this->render('ventas/index.html.twig'); }

    #[Route('/pedidos/monitor', name: 'app_pedidos_monitor')]
    public function monitor(): Response { return $this->render('pedidos/monitor.html.twig'); }

    #[Route('/pedidos/cocina', name: 'app_pedidos_cocina')]
    public function cocina(): Response { return $this->render('pedidos/cocina.html.twig'); }

    #[Route('/pedidos/gestion', name: 'app_pedidos_gestion')]
    public function gestion(): Response { return $this->render('pedidos/gestion.html.twig'); }

    #[Route('/api/rutas-permisos', name: 'app_rutas_permisos', methods: ['GET'])]
    public function rutasPermisos(RouterInterface $router): JsonResponse
    {
        $publicas = [
            '/',
            '/login',
            '/logout',
            '/puestos-trabajo',
            '/pedidos/monitor',
        ];
        $rutas = [];
        $etiquetas = [
            '/dashboard' => 'Dashboard',
            '/gestion/puestos-trabajo' => 'Puestos de trabajo',
            '/informes/balance' => 'Balance general',
        ];

        foreach ($router->getRouteCollection() as $route) {
            $path = $route->getPath();
            $methods = $route->getMethods();
            if (($methods && !in_array('GET', $methods, true))
                || str_contains($path, '{')
                || in_array($path, $publicas, true)
                || str_starts_with($path, '/_')
                ) {
                continue;
            }

            $rutas[$path] = $etiquetas[$path] ?? $this->etiquetaRuta($path);
        }

        ksort($rutas);
        return new JsonResponse(array_map(
            static fn(string $url, string $label): array => ['url' => $url, 'label' => $label],
            array_keys($rutas),
            array_values($rutas)
        ));
    }

    private function etiquetaRuta(string $path): string
    {
        $label = trim(str_replace(['/', '-'], [' ', ' '], $path));
        return mb_convert_case($label ?: 'Inicio', MB_CASE_TITLE, 'UTF-8');
    }
}
