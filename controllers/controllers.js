app.controller('ComposerCtrl', ['$scope', '$mdDialog', '$mdToast', 'Me', 'Upload', 'apiBase', '$timeout', '$document', '$q', function($scope, $mdDialog, $mdToast, Me, Upload, apiBase, $timeout, $document, $q) {
    $scope.files = [];

    var isUploadFinished = function() {
        finished = true;

        angular.forEach($scope.files, function(file, key) {
            if (file.complete !== true) {
                finished = false;
            }

        });
        return finished;
    };

    $scope.removePicture = function(i) {

        $scope.files.splice(i, 1);
    };

    var uploadImages = function(files) {
        var d = $q.defer();
        var finished = [];

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
                    //console.log(response);
                    finished.push(response.data.image_id);

                    if (files.length == finished.length) {
                        d.resolve(finished);
                    }
                }, function(response) {
                    file.failed = true;
                }, function(evt) {
                    file.progress = Math.min(100, parseInt(100.0 *
                        evt.loaded / evt.total));
                });
            });

            return d.promise;

        }
    };

    $scope.composePost = function() {
        post = $scope.composer.post;
        anonymous = $scope.composer.anonymous;

        if (anonymous !== 1 || anonymous !== 0) {
            anonymous = 0;
        }

        var createPost = function(o) {
            //console.log(Me.sendPost);

            Me.sendPost({
                post: o.p,
                anonymous: o.a,
                images: o.i
            }).then(function(r) {
                $scope.disabledBtn = true;
                //console.log(r);
                //console.log($scope.posts);
                if (r.status === 201) {
                    $scope.posts.unshift(r);
                    console.log($scope.posts);
                }
            }, function(r) {


            });
        };

        if ($scope.files.length) {
            uploadImages($scope.files).then(
                function(id_arr) {
                    //console.log(id_arr);
                    createPost({
                        p: post,
                        a: anonymous,
                        i: id_arr
                    });
                });
        } else {
            createPost({
                p: post,
                a: anonymous,
                i: []
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

app.controller('LoginCtrl', ['$scope', 'Facebook', '$location', '$state', 'Auth', 'Me', 'appBase', function($scope, Facebook, $location, $state, Auth, Me, appBase) {

    if (Auth.userProfile.authenticated === true) {
        $state.go('home');
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

    $scope.loginFb = function() {
        //console.log("loginctrl");

        Facebook.login(function(r) {
            //console.log(r);
            if (r.status === 'connected') {
                Auth.signinFb(r.authResponse.accessToken).then(function(r) {
                    //console.log(r);
                }, function(r) {
                    //console.log(r);
                });
            } else {
                return "Login failed";
            }
        });


        //console.log($scope.loggedIn);

        /*Facebook.login().then(function() {
            refresh();
            console.log("ok");
        });*/
        //var v = v++;

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
            console.log($scope.user);
            $state.go('home');
            //Me.callInit();
            //Success Login
        }, function(err) {
            console.log(err);
            //console.log($scope.loginDetails);
        });
    };

}]);

app.controller('SignupCtrl', ['$scope', 'Facebook', 'Auth', '$location', '$mdDialog', function($scope, Facebook, Auth, $location, $mdDialog) {
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
