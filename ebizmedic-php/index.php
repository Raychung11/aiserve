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
        'home'                          => ['HomeController',         'index'],
        'doctors'                       => ['HomeController',         'doctors'],
        'doctors/show'                  => ['HomeController',         'showDoctor'],
        'booking'                       => ['HomeController',         'booking'],
        'booking/slots'                 => ['HomeController',         'slots'],

        'login'                         => ['AuthController',         'loginForm'],
        'register'                      => ['AuthController',         'registerForm'],
        'logout'                        => ['AuthController',         'logout'],

        // Admin
        'admin/dashboard'               => ['AdminController',        'dashboard'],
        'admin/doctors'                 => ['AdminController',        'doctors'],
        'admin/doctors/create'          => ['AdminController',        'createDoctor'],
        'admin/doctors/edit'            => ['AdminController',        'editDoctor'],
        'admin/organisations'           => ['AdminController',        'organisations'],
        'admin/organisations/create'    => ['AdminController',        'createOrganisation'],
        'admin/appointments'            => ['AdminController',        'appointments'],
        'admin/users'                   => ['AdminController',        'users'],
        'admin/approvals'               => ['AdminController',        'approvals'],
        'admin/reports'                 => ['AdminController',        'reports'],
        'admin/settings'                => ['AdminController',        'settings'],

        // Medic
        'medic/dashboard'               => ['MedicController',        'dashboard'],
        'medic/appointments'            => ['MedicController',        'appointments'],
        'medic/schedule'                => ['MedicController',        'schedule'],
        'medic/profile'                 => ['MedicController',        'profile'],
        'medic/records'                 => ['MedicController',        'records'],
        'medic/records/create'          => ['MedicController',        'createRecord'],

        // Organisation
        'organisation/dashboard'        => ['OrganisationController', 'dashboard'],
        'organisation/doctors'          => ['OrganisationController', 'doctors'],
        'organisation/doctors/add'      => ['OrganisationController', 'addDoctor'],
        'organisation/services'         => ['OrganisationController', 'services'],
        'organisation/appointments'     => ['OrganisationController', 'appointments'],
        'organisation/profile'          => ['OrganisationController', 'profile'],

        // Pharmacist
        'pharmacist/dashboard'          => ['PharmacistController',   'dashboard'],
        'pharmacist/dispense'           => ['PharmacistController',   'dispense'],
        'pharmacist/history'            => ['PharmacistController',   'history'],
        'pharmacist/medicines'          => ['PharmacistController',   'medicines'],

        // Organisation — dispensary
        'organisation/dispensary'         => ['OrganisationController', 'dispensary'],
        'organisation/dispensary/add'     => ['OrganisationController', 'addMedicine'],
        'organisation/dispensary/edit'    => ['OrganisationController', 'editMedicine'],
        'organisation/dispensary/history' => ['OrganisationController', 'dispensaryHistory'],
        'organisation/dispensary/stock'   => ['OrganisationController', 'stockForm'],

        // Admin dispensary
        'admin/dispensary'              => ['AdminController',        'dispensary'],

        // User
        'user/dashboard'                => ['UserController',         'dashboard'],
        'user/appointments'             => ['UserController',         'appointments'],
        'user/records'                  => ['UserController',         'records'],
        'user/dispensary'               => ['UserController',         'dispensary'],
        'user/profile'                  => ['UserController',         'profile'],

        // Medic dispensary
        'medic/dispensary'              => ['MedicController',        'dispensary'],

        // Phase 4
        'user/health-profile'           => ['UserController',         'healthProfile'],
        'notifications'                 => ['NotificationController', 'index'],
        'dispensings/invoice'           => ['InvoiceController',      'show'],

        // Consultation
        'consultation/lobby'            => ['ConsultationController', 'lobby'],
        'consultation/room'             => ['ConsultationController', 'room'],
        'consultation/messages'         => ['ConsultationController', 'messages'],
    ],
    'POST' => [
        'login'                         => ['AuthController',         'login'],
        'register'                      => ['AuthController',         'register'],

        'admin/doctors/store'           => ['AdminController',        'storeDoctor'],
        'admin/doctors/update'          => ['AdminController',        'updateDoctor'],
        'admin/doctors/delete'          => ['AdminController',        'deleteDoctor'],
        'admin/organisations/store'     => ['AdminController',        'storeOrganisation'],
        'admin/appointments/update'     => ['AdminController',        'updateAppointment'],
        'admin/users/toggle'            => ['AdminController',        'toggleUser'],
        'admin/approvals/action'        => ['AdminController',        'approvalAction'],
        'admin/settings/update'         => ['AdminController',        'updateSettings'],

        'medic/schedule/update'         => ['MedicController',        'updateSchedule'],
        'medic/appointments/update'     => ['MedicController',        'updateAppointment'],
        'medic/toggle-availability'     => ['MedicController',        'toggleAvailability'],
        'medic/profile/update'          => ['MedicController',        'updateProfile'],
        'medic/profile/photo'           => ['MedicController',        'updatePhoto'],
        'medic/profile/password'        => ['MedicController',        'changePassword'],
        'medic/records/store'           => ['MedicController',        'storeRecord'],

        'organisation/doctors/store'    => ['OrganisationController', 'storeDoctor'],
        'organisation/services/store'   => ['OrganisationController', 'storeService'],
        'organisation/services/delete'  => ['OrganisationController', 'deleteService'],
        'organisation/profile/update'   => ['OrganisationController', 'updateProfile'],
        'organisation/profile/photo'    => ['OrganisationController', 'updatePhoto'],
        'organisation/profile/password' => ['OrganisationController', 'changePassword'],

        'booking/store'                      => ['HomeController',         'storeBooking'],
        'user/profile/update'                => ['UserController',         'updateProfile'],
        'user/profile/photo'                 => ['UserController',         'updatePhoto'],
        'user/profile/password'              => ['UserController',         'changePassword'],
        'user/health-profile/update'         => ['UserController',         'updateHealthProfile'],
        'user/appointments/rate'             => ['UserController',         'rateDoctor'],
        'user/appointments/cancel'           => ['UserController',         'cancelAppointment'],
        'notifications/read'                 => ['NotificationController', 'markRead'],

        // Pharmacist
        'pharmacist/dispense/store'          => ['PharmacistController',   'storeDispensing'],

        // Organisation dispensary
        'organisation/dispensary/store'      => ['OrganisationController', 'storeMedicine'],
        'organisation/dispensary/update'     => ['OrganisationController', 'updateMedicine'],
        'organisation/dispensary/stock/save' => ['OrganisationController', 'saveStock'],
        'organisation/dispensary/staff/store'=> ['OrganisationController', 'storePharmacist'],

        // Admin
        'admin/dispensary/dispense'          => ['AdminController',        'dispenseAdmin'],

        // Consultation
        'consultation/chat'                  => ['ConsultationController', 'sendMessage'],
        'consultation/end'                   => ['ConsultationController', 'endCall'],
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
