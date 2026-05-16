<?php

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/src/Auth.php';
require_once __DIR__ . '/src/helpers.php';

// Auto-load controllers and models
spl_autoload_register(function (string $class) {
    $dirs = [__DIR__ . '/controllers/', __DIR__ . '/models/'];
    foreach ($dirs as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

Auth::start();

// Determine the current URL segment
$url = trim($_GET['url'] ?? '', '/');
if ($url === '') $url = 'home';

$method = $_SERVER['REQUEST_METHOD'];

// ---------------------------------------------------------------
// Route table: [method => [url => [Controller, method]]]
// ---------------------------------------------------------------
$routes = [
    'GET' => [
        'home'                        => ['HomeController',         'index'],
        'doctors'                     => ['HomeController',         'doctors'],
        'doctors/show'                => ['HomeController',         'showDoctor'],
        'booking'                     => ['HomeController',         'booking'],

        'login'                       => ['AuthController',         'loginForm'],
        'register'                    => ['AuthController',         'registerForm'],
        'logout'                      => ['AuthController',         'logout'],

        // Admin
        'admin/dashboard'             => ['AdminController',        'dashboard'],
        'admin/doctors'               => ['AdminController',        'doctors'],
        'admin/doctors/create'        => ['AdminController',        'createDoctor'],
        'admin/doctors/edit'          => ['AdminController',        'editDoctor'],
        'admin/organisations'         => ['AdminController',        'organisations'],
        'admin/organisations/create'  => ['AdminController',        'createOrganisation'],
        'admin/appointments'          => ['AdminController',        'appointments'],
        'admin/users'                 => ['AdminController',        'users'],
        'admin/reports'               => ['AdminController',        'reports'],
        'admin/settings'              => ['AdminController',        'settings'],

        // Medic
        'medic/dashboard'             => ['MedicController',        'dashboard'],
        'medic/appointments'          => ['MedicController',        'appointments'],
        'medic/schedule'              => ['MedicController',        'schedule'],
        'medic/profile'               => ['MedicController',        'profile'],

        // Organisation
        'organisation/dashboard'      => ['OrganisationController', 'dashboard'],
        'organisation/doctors'        => ['OrganisationController', 'doctors'],
        'organisation/doctors/add'    => ['OrganisationController', 'addDoctor'],
        'organisation/services'       => ['OrganisationController', 'services'],
        'organisation/appointments'   => ['OrganisationController', 'appointments'],
        'organisation/profile'        => ['OrganisationController', 'profile'],

        // User
        'user/dashboard'              => ['UserController',         'dashboard'],
        'user/appointments'           => ['UserController',         'appointments'],
        'user/profile'                => ['UserController',         'profile'],
    ],
    'POST' => [
        'login'                       => ['AuthController',         'login'],
        'register'                    => ['AuthController',         'register'],

        'admin/doctors/store'         => ['AdminController',        'storeDoctor'],
        'admin/doctors/update'        => ['AdminController',        'updateDoctor'],
        'admin/doctors/delete'        => ['AdminController',        'deleteDoctor'],
        'admin/organisations/store'   => ['AdminController',        'storeOrganisation'],
        'admin/appointments/update'   => ['AdminController',        'updateAppointment'],
        'admin/users/toggle'          => ['AdminController',        'toggleUser'],
        'admin/settings/update'       => ['AdminController',        'updateSettings'],

        'medic/schedule/update'       => ['MedicController',        'updateSchedule'],
        'medic/appointments/update'   => ['MedicController',        'updateAppointment'],
        'medic/profile/update'        => ['MedicController',        'updateProfile'],

        'organisation/doctors/store'  => ['OrganisationController', 'storeDoctor'],
        'organisation/services/store' => ['OrganisationController', 'storeService'],
        'organisation/services/delete'=> ['OrganisationController', 'deleteService'],
        'organisation/profile/update' => ['OrganisationController', 'updateProfile'],

        'booking/store'               => ['HomeController',         'storeBooking'],
        'user/profile/update'         => ['UserController',         'updateProfile'],
    ],
];

// Dispatch
$routeMap = $routes[$method] ?? [];

if (isset($routeMap[$url])) {
    [$controllerClass, $action] = $routeMap[$url];
    $controller = new $controllerClass();
    $controller->$action();
} else {
    http_response_code(404);
    view('errors/404');
}
