app.controller('ComposerCtrl', ['$scope', '$mdDialog', 'Me', 'Upload', 'apiBase', '$timeout', function($scope, $mdDialog, Me, Upload, apiBase, $timeout) {

    $scope.composePost = function() {
        p = $scope.composer.post;
        a = $scope.composer.anonymous;

        if (a !== 1 || a !== 0) {
            a = 0;
        }

        if (p) {
            Me.createPost(p, a).then(function(r) {
                $scope.disabledBtn = true;
                //console.log(r);
                //console.log($scope.posts);
                var composedPost = {
                    //"post_id": r.data.post_id,
                    "anonymous": false,
                    "testimony": p,
                    "time": Date.now(),
                    "timestamp": Date.now(),
                    "user": {
                        "user_id": Me.id,
                        "avatar": Me.profile.avatar,
                        "name": Me.profile.name
                    }
                };
                $scope.posts.unshift(composedPost);
                console.log($scope.posts);

            }, function(r) {

            });
        }

    };

    $scope.files = null;

    $scope.uploadFiles = function(files) {
        $scope.files = files;
        if (files && files.length) {
            angular.forEach(files, function(file) {
                file.upload = Upload.upload({
                    url: apiBase + '/images',
                    data: {
                        file: file
                    }
                });

                file.upload.then(function(response) {
                    file.complete = true;
                    file.result = response.data;

                }, function(response) {
                    file.failed = true;
                }, function(evt) {
                    file.progress = Math.min(100, parseInt(100.0 *
                        evt.loaded / evt.total));
                });
            });
        }
    };


}]);

app.controller('PostCtrl', ['AppService', '$scope', 'Restangular', '$mdToast', '$document', function(AppService, $scope, Restangular, $mdToast, $document) {
    $scope.posts = []; //Post.getList();
    $scope.loading = false;

    var loadPosts = function() {

        $scope.loading = true;

        AppService.getPosts.getList().then(function(r) {
            $scope.posts = r.data;
            $scope.loading = false;
            //console.log(r.data.plain());
        }, function(err) {
            $scope.loading = false;
            console.log(err);
            $mdToast.show(
                $mdToast.simple()
                .content('Something\'s  Wrong!')
                .position('top left')
                .parent($document[0].querySelector('.main'))
                .hideDelay(3000)
            );
        });
    };

    loadPosts();
}]);

app.controller('LoginCtrl', ['$scope', '$facebook', '$location', 'Auth', 'Me', 'appBase', function($scope, $facebook, $location, Auth, Me, appBase) {

    if (Auth.userProfile.authenticated === true) {
        $location.path(appBase + "/");
    }


    $scope.login = function() {
        $facebook.login().then(function() {
            refresh();
            console.log("scope.login");
        });
    };

    var refresh = function() {
        $facebook.api("/me", {
            fields: 'id,name,email'
        }).then(
            function(response) {
                $scope.welcomeMsg = "Welcome " + response.name;
                console.log(response);
                console.log(JSON.stringify(response));
            },
            function(err) {
                $scope.welcomeMsg = "Please log in";
            });
    };

    $scope.submitLogin = function() {
        Auth.signin($scope.loginDetails).then(function(r) {
            console.log($scope.user);
            //Me.callInit();
            //Success Login
        }, function(err) {
            console.log(err);
            //console.log($scope.loginDetails);
        });
    };

}]);

app.controller('SignupCtrl', ['$scope', '$facebook', 'Auth', '$location', '$mdDialog', function($scope, $facebook, Auth, $location, $mdDialog) {
    var refresh;

    $scope.newUser = {};
    $scope.signupFb = function() {
        $facebook.login().then(function() {
            refresh();
        });
    };

    refresh = function() {
        $facebook.api("/me", {
            fields: 'id,first_name,last_name,email'
        }).then(
            function(response) {
                $facebook.logout();
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
            Auth.signup($scope.newUser).then(function(response) {
                Auth.saveToken(response.token);
                console.log(response);
            });
        }
    };

}]);

app.controller('LogoutCtrl', ['$scope', 'Auth', 'Me', function($scope, Auth, Me) {
    Auth.logout();
    //$scope.logout();
    console.log("mayama");
}]);
