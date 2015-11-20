app.factory('AppService', ['Restangular', 'Auth', 'Me', function(Restangular, Auth, Me) {

    Auth.refreshProfile().then(function(r) {
        //Me.callInit();
        //console.log("letsee");
    });
    //console.log("appService");

    var search = Restangular.service('search');

    var getPosts = Restangular.all('posts');

    var getCategories = Restangular.all('tags').getList();


    return {
        search: search,
        getPosts: getPosts,
        getCategories: getCategories

    };
}]);

app.factory('Auth', ['$http', '$localStorage', 'Restangular', '$q', '$state', 'Facebook', function($http, $localStorage, Restangular, $q, $state, Facebook) {
    var user = {
        authenticated: false,
        user_id: null,
        name: "Guest",
        firstName: "Guest",
        lastName: "Guest",
        sex: null,
        location: null,
        email: null,
        avatar: "img/guest.png",
    };

    Restangular.setFullResponse(true);

    function urlBase64Decode(str) {
        var output = str.replace('-', '+').replace('_', '/');
        switch (output.length % 4) {
            case 0:
                break;
            case 2:
                output += '==';
                break;
            case 3:
                output += '=';
                break;
            default:
                throw 'Illegal base64url string!';
        }
        return window.atob(output);
    }

    function getClaimsFromToken() {
        var token = $localStorage.token;
        var claims = {};

        //console.log(token);
        if (typeof token !== 'undefined') {
            var encoded = token.split('.')[1];
            claims = JSON.parse(urlBase64Decode(encoded));
        }

        return claims;
    }

    var refreshProfile = function() {
        //console.log("refresh")
        var d = $q.defer();
        if (getClaimsFromToken().user_id) {
            //console.log("truthy");
            //console.log(getClaimsFromToken().user_id);

            Restangular.one('users', getClaimsFromToken().user_id).get().then(function(r) {
                buildAuthProfile(r.data);
                d.resolve(true);
                //console.log(getClaimsFromToken().user_id);
            }, function(r) {
                if (r.status == 404) {
                    resetProfile();
                    logout();
                }
                d.resolve(false);
            });
        } else {
            //console.log("falsy");
            resetProfile();
        }

        return d.promise;
    };

    var buildAuthProfile = function(u) {
        //console.log(user, u);
        user.authenticated = true;
        user.user_id = u.user_id;
        user.name = u.first_name + ' ' + u.last_name;
        user.firstName = u.first_name;
        user.lastName = u.last_name;
        user.sex = u.sex;
        user.location = u.state + ' ' + u.country;
        user.email = u.email;
        user.avatar = u.avatar;
    };

    var saveToken = function(token) {

        $localStorage.token = token;
        user.token = token;
    };

    var signup = function(newUser) {

        return Restangular.all('users').post(newUser);
    };

    var signin = function(l) {
        var d = $q.defer();

        Restangular.service('authenticate').post(l).then(function(r) {
            //console.log(r);
            saveToken(r.data.token);
            refreshProfile(); //Refresh session data here
            //$scope.refreshProfile();
            $state.go('home');
            d.resolve(r.data.token);
        }, function() {
            d.reject();
        });

        return d.promise;
    };

    var signinFB = function() {
        //console.log("loginctrl");
        var d = $q.defer();

        Facebook.login(function(r) {
            //console.log(r);
            if (r.status === 'connected') {
                json = {
                    "fb_access_token": r.authResponse.accessToken
                };
                Restangular.service('fb-token').post(json).then(function(r) {
                    //console.log(r);
                    saveToken(r.data.token);
                    refreshProfile(); //Refresh session data here
                    //$scope.refreshProfile();
                    //$state.go('home');
                    d.resolve(r.data.token);
                }, function(r) {
                    d.reject(r);
                });
            } else {
                d.reject(false);
                return "Login failed";
            }
        });

        return d.promise;
    };

    var logout = function() {
        tokenClaims = {};
        delete $localStorage.token;
        refreshProfile();
        $state.go('login');
    };

    var resetProfile = function() {
        user.authenticated = false;
        user.user_id = null;
        user.name = "Guest";
        user.firstName = "Guest";
        user.lastName = "Guest";
        user.sex = null;
        user.location = null;
        user.email = null;
        user.avatar = "img/guest.png";
    };

    return {
        signup: signup,
        signin: signin,
        signinFB: signinFB,
        logout: logout,
        refreshProfile: refreshProfile,
        resetProfile: resetProfile,
        userProfile: user,
        token: getClaimsFromToken()
    };
}]);

app.factory('Me', ['Auth', 'Restangular', '$q', function(Auth, Restangular, $q) {

    var uid = Auth.token.user_id;

    var me = Restangular.one('users', uid);

    var sendPost = function(o) {
        return me.post("posts", {
            "post": o.post,
            "anonymous": o.anonymous,
            "images": o.images
        });
    };

    return {
        id: uid,
        me: me,
        authenticated: Auth.userProfile.authenticated,
        profile: Auth.userProfile,
        sendPost: sendPost

    };
}]);

app.factory('PostService', ['Restangular', '$q', function(Restangular, $q) {

    var posts = Restangular.all('posts');

    var post = function(id) {
        return Restangular.one('posts', id);
    };

    return {
        post: post
    };

}]);

app.factory('SocialService', ['Facebook', 'Auth', function(Facebook, Auth) {


    return {

    };
}]);

app.factory('UXService', ['$mdDialog', 'Auth', '$q', function($mdDialog, Auth, $q) {

    var signinModal = function(ev) {
        var d = $q.defer();
        $mdDialog.show({
                controller: 'UXModalLoginCtrl',
                templateUrl: 'partials/ux.signin.modal.html',
                parent: angular.element(document.body),
                targetEvent: ev,
                clickOutsideToClose: true
            })
            .then(function(res) {
                d.resolve(res);
            }, function() {
                d.reject();
            });

        return d.promise;
    };

    var UXLoginFB = function() {
        Auth.signinFB().then(function() {
            $mdDialog.hide(true);
        });
    };



    return {

        signinModal: signinModal,
        UXLoginFB: UXLoginFB

    };
}]);
