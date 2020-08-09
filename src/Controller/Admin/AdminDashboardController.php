<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Security\Permission;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class AdminDashboardController extends AbstractDashboardController
{
    /**
     * @Route("/admin", name="app.admin")
     */
    public function index(): Response
    {
        $routeBuilder = $this->get(CrudUrlGenerator::class)->build();

        return $this->redirect($routeBuilder->setController(UserCrudController::class)->generateUrl());
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Vacansee API');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linktoDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToRoute('Website', 'fa fa-globe', 'app.home');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        $userMenuItems = [];
        if ($this->isGranted(Permission::EA_EXIT_IMPERSONATION)) {
            $userMenuItems[] = MenuItem::linkToExitImpersonation('__ea__user.exit_impersonation', 'fa-user-lock');
        }

        return UserMenu::new()
            ->displayUserName()
            ->displayUserAvatar()
            ->setName(method_exists($user, '__toString') ? (string)$user : $user->getUsername())
            ->setAvatarUrl(null)
            ->setMenuItems($userMenuItems);
    }
}
