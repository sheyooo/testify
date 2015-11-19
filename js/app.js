//"use strict";

var app = angular.module('testify', ['ngMaterial',
    'chieffancypants.loadingBar',
    'ngAnimate',
    'ngMessages',
    'angularMoment',
    'ui.router',
    'ngStorage',
    'restangular',
    'facebook',
    'ngFileUpload',
    'ngTextTruncate'
]);

//app.constant('apiBase', "http://localhost/testify/api");
app.constant('appUrl', "https://testify-for-testimonies.herokuapp.com");
app.constant('appBase', "/testify");
app.constant('apiBase', "/testify/api");

app.config(function(FacebookProvider, $httpProvider, RestangularProvider, apiBase) {
    FacebookProvider.setAppId(180042792329807);
    RestangularProvider.setBaseUrl(apiBase);

    $httpProvider.interceptors.push(['$q', '$location', '$localStorage', function($q, $location, $localStorage) {
        return {
            'request': function(config) {
                config.headers = config.headers || {};
                if ($localStorage.token) {
                    config.headers.Authorization = 'Bearer ' + $localStorage.token;
                }
                return config;
            },
            'response': function(response) {
                if (t = response.headers('Authorization')) {
                    $localStorage.token = t;
                    //console.log(t);
                }

                return response;
            },
            'responseError': function(response) {
                if (response.status === 401 && response.data.status == "Invalid Authorization" || response.status === 403) {
                    if (response.data.status == "Invalid Token") {
                        delete $localStorage.token;
                        $location.path('/signin');
                    }
                    console.log("Unauthorized or forbidden");

                }
                return $q.reject(response);
            }
        };
    }]);
});

app.run(function() {
    // Cut and paste the "Load the SDK" code from the facebook javascript sdk page.

    // Load the facebook SDK asynchronously
    /*(function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) {
            return;
        }
        js = d.createElement(s);
        js.id = id;
        js.src = "//connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));*/
});

app.config(function($mdThemingProvider, $mdIconProvider) {
    var myPaletteMap = $mdThemingProvider.extendPalette('blue', {

        'contrastDefaultColor': 'light',
        'contrastDarkColors': '200, 300',
        'contrastLightColors': '500',
        '500': '97B6CA',
        '50': '1C3456',
        '100': '5DADE0',
        '200': 'e74c3c',
        '300': 'FFFFFF',
        '400': 'F4F4F4',
        '600': '27ae60',
        '700': '77777E'
    });
    $mdThemingProvider.definePalette('palette', myPaletteMap);
    //console.log(myPaletteMap);
    $mdThemingProvider.theme('default')
        .primaryPalette('palette', {
            'default': '500',
            'hue-1': '50',
            'hue-2': '100',
            'hue-3': '300',
        })
        .accentPalette('palette', {
            'default': '200',
            'hue-1': '600',
            'hue-2': '700'

        })
        .warnPalette('red', {

        });

    $mdThemingProvider.theme('input', 'default')
        .primaryPalette('grey', {

        })
        .backgroundPalette('palette', {
            'default': '500'
        }).dark();

    $mdThemingProvider.theme('search', 'default')
        .primaryPalette('yellow', {

        })
        .backgroundPalette('palette', {
            'default': '50'
        }).dark();

    $mdIconProvider.defaultFontSet("mdi", "mdi-");
});

app.config(function($stateProvider, $locationProvider, appBase) {
    $stateProvider
        .state('home', {
            url: appBase + '/',
            views: {
                "leftNav": {
                    templateUrl: "partials/left-sidenav.html"
                },
                "MainContent": {
                    templateUrl: 'views/home.html'
                },
                "rightNav": {
                    templateUrl: "partials/right-sidenav.html"
                }
            }
        }).state('login', {
            url: appBase + '/login',
            views: {
                "leftNav": {},
                "MainContent": {
                    templateUrl: 'views/login.html'
                },
                "rightNav": {}
            }
        }).state('signup', {
            url: appBase + '/signup',
            controller: 'LoginCtrl',
            views: {
                "leftNav": {},
                "MainContent": {
                    templateUrl: 'views/signup.html'
                },
                "rightNav": {}
            }
        }).state('logout', {
            url: appBase + '/logout',
            views: {
                "leftNav": {
                    template: " "
                },
                "MainContent": {
                    controller: 'LogoutCtrl',
                    template: " "
                },
                "rightNav": {
                    template: " "
                }
            }
        }).state('entrance', {
            url: appBase + '/entrance',
            views: {
                "leftNav": {},
                "MainContent": {
                    templateUrl: 'views/entrance.html'
                },
                "rightNav": {}
            }
        }).state('profile', {
            url: appBase + '/profile',
            templateUrl: 'views/profile.html',
            views: {
                "leftNav": {
                    templateUrl: "partials/left-sidenav.html"
                },
                "MainContent": {
                    templateUrl: "views/profile.html"
                },
                "rightNav": {
                    templateUrl: "partials/right-sidenav.html"

                }
            }
        });
    /*.otherwise({
                redirectTo: appBase + '/'
            });*/

    $locationProvider.html5Mode({
        enabled: true,
        requireBase: false
    });
});
