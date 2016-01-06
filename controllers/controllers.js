app.controller('PostsCtrl', ['AppService', 'Me', '$scope', '$state', '$stateParams', 'Restangular', 'UXService', '$document', function(AppService, Me, $scope, $state, $stateParams, Restangular, UXService, $document) {
    $scope.app = AppService.app; //Post.getList();
    $scope.post_stats = {
        loading: false
    };
    var loadPosts = function() {
        var param = null;
        $scope.post_stats.loading = true;

        if ($stateParams.cat) {
            load({
                category: $stateParams.cat
            });
        } else {
            load({});
        }


        function load(param) {
            AppService.getPosts.getList(param).then(function(r) {
                //console.log(AppService.app);
                AppService.app.posts = r.data;
                /*l = r.data.length;
                for (i = 0; i < l; i++) {
                    AppService.app.posts.unshift(r.data[i]);
                }*/

                $scope.post_stats.loading = false;
                //console.log(r.data.plain());
            }, function(err) {
                $scope.post_stats.loading = false;
                //console.log(err);
                UXService.toast("Something's wrong");
            });
        }
    };

    loadPosts();



}]);

app.controller('LoginCtrl', ['$scope', 'UXService', 'Facebook', '$q', '$state', 'Auth', 'Me', 'appBase', function($scope, UXService, Facebook, $q, $state, Auth, Me, appBase) {

    if (Auth.userProfile.authenticated === true) {
        $state.go('web.app.dashboard.home');
    }

    $scope.fb_button = "Login with Facebook";

    Facebook.getLoginStatus(function(r) {
        //console.log(r);
        if (r.status === 'connected') {
            //console.log(r);
            Facebook.api('/me', function(r) {
                $scope.fb_logged_in = true;
                $scope.fb_name = r.name;
                $scope.fb_button = "Continue as " + $scope.fb_name;
            });
            //console.log(r);
        } else {
            //console.log("false;");                
            $scope.fb_logged_in = false;
            $scope.fb_name = "null";
            $scope.fb_button = "Login with Facebook";
        }
    });

    $scope.loginFB = function() {
        Auth.signinFB().then(function() {
            $state.go('web.app.dashboard.home');
        }, function(r) {
            UXService.toast(r.data.error);
        });
    };

    $scope.UXLoginFB = function() {
        UXService.UXLoginFB();
    };

    $scope.UXSubmitLogin = function() {
        UXService.UXSubmitLogin($scope.loginDetails);
    };

    var refresh = function() {
        Facebook.api("/me", {
            fields: 'id,name,email,access_token'
        }).then(
            function(response) {
                $scope.welcomeMsg = "Welcome " + response.name;
                console.log(response);
                //console.log(JSON.stringify(response));
            },
            function(err) {
                $scope.welcomeMsg = "Please log in";
            });
    };

    $scope.submitLogin = function() {
        Auth.signin($scope.loginDetails).then(function(r) {
            //console.log($scope.user);
            $state.go('web.app.dashboard.home');
            //Me.callInit();
            //Success Login
        }, function(err) {
            console.log(err);
            //console.log($scope.loginDetails);
        });
    };

}]);

app.controller('SignupCtrl', ['$scope', 'Facebook', 'Auth', '$location', '$mdDialog', '$state', function($scope, Facebook, Auth, $location, $mdDialog, $state) {
    var refresh;

    $scope.newUser = {};
    $scope.signupFb = function() {
        Facebook.login().then(function() {
            refresh();
        });
    };

    refresh = function() {
        Facebook.api("/me", {
            fields: 'id,first_name,last_name,email'
        }).then(
            function(response) {
                Facebook.logout();
                //$scope.welcomeMsg = "Welcome " + response.name;
                //console.log(response);
                //console.log(JSON.stringify(response));
                //Auth.BuildSession(name, id, lastname for appctrl scope);
                //$location.path('/');
            },
            function(err) {
                $mdDialog.show(
                    $mdDialog.alert()
                    .parent(angular.element(document.querySelector('body')))
                    .clickOutsideToClose(true)
                    .title('Login failed!')
                    //.content('Login failed')
                    .ariaLabel('Failed login')
                    .ok('close')
                    //.targetEvent(ev)
                );

                //$scope.welcomeMsg = "Please log in";
            });
    };

    $scope.submitForm = function() {
        //console.log($scope.newUser);
        //console.log($scope.signupForm.$valid);
        if ($scope.signupForm.$valid) {
            Auth.signup($scope.newUser).then(
                function(r) {

                    $state.go('web.app.dashboard.home');
                    //console.log(r);
                },
                function(r) {
                    //console.log(r);
                });
        }
    };

}]);

app.controller('LogoutCtrl', ['$scope', 'Auth', 'Me', function($scope, Auth, Me) {
    Auth.logout();
    //$scope.logout();
    console.log("mayama");
}]);

app.controller('ProfileCtrl', ['Restangular', '$scope', '$stateParams', '$state', function(Restangular, $scope, $stateParams, $state) {
    //console.log(profile);
    var user_profile = Restangular.one('users', $stateParams.hash_id);

    $scope.user = {
        profile: {},
        activities: [],
        favorites: [],
        taps: []
    };


    var loadUserProfile = function() {
        user_profile.one('profile').get().then(function(r) {
            $scope.user.profile = r.data;
        });
    };

    var loadUserPosts = function() {
        user_profile.all('activities').getList().then(function(r) {
            $scope.user.activities = r.data;
        });
    };

    var loadUserFavorites = function() {
        user_profile.all('favorites').getList().then(function(r) {
            $scope.user.favorites = r.data;
        });
    };

    var loadUserTaps = function() {
        user_profile.all('taps').getList().then(function(r) {
            $scope.user.taps = r.data;
        });
    };

    loadUserProfile();
    loadUserPosts();
    loadUserFavorites();
    loadUserTaps();


}]);

app.controller('UXModalLoginCtrl', ['$scope', '$mdDialog', function($scope, $mdDialog) {
    $scope.hide = function() {
        $mdDialog.hide();
    };
    $scope.cancel = function() {
        $mdDialog.cancel();
    };
    $scope.answer = function(answer) {
        $mdDialog.hide(answer);
    };
}]);

app.controller('UXModalPostCategorizeCtrl', ['$scope', '$mdDialog', 'AppService', function($scope, $mdDialog, AppService) {

    AppService.getCategories.then(function(res) {
        $scope.categories = res.data;
    });
    $scope.hide = function() {
        $mdDialog.hide();
    };
    $scope.cancel = function() {
        $mdDialog.cancel();
    };
    $scope.filePostIn = function(id) {
        console.log($scope.selectedCategories);

        $mdDialog.hide(id);
    };
}]);
