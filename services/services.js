app.factory('AppService', ['Restangular', 'Auth', 'Me', function(Restangular, Auth, Me) {

    Auth.refreshProfile().then(Me.callInit());

    var search = Restangular.service('search');

    var getPosts = Restangular.all('posts');

    var getCategories = Restangular.all('tags').getList();


    return {
        search: search,
        getPosts: getPosts,
        getCategories: getCategories

    };
}]);

app.factory('Auth', ['$http', '$localStorage', 'Restangular', '$q', '$state', function($http, $localStorage, Restangular, $q, $state) {
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

    var getClaimsFromToken = function() {
        var token = $localStorage.token;
        var claims = {};

        //console.log(token);
        if (typeof token !== 'undefined') {
            var encoded = token.split('.')[1];
            claims = JSON.parse(urlBase64Decode(encoded));
        }

        return claims;
    };

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
            $location.path('/');
            d.resolve(r.data.token);
        }, function() {
            d.reject();
        });

        return d.promise;
    };

    var signinFb = function(fbtk) {
        var d = $q.defer();
        //console.log(fbtk);
        json = {
            "fb_access_token": fbtk
        };

        Restangular.service('fb-token').post(json).then(function(r) {
            //console.log(r);
            saveToken(r.data.token);
            refreshProfile(); //Refresh session data here
            //$scope.refreshProfile();
            $state.go('home');
            d.resolve(r.data.token);
        }, function(r) {
            d.reject(r);
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
        signinFb: signinFb,
        logout: logout,
        refreshProfile: refreshProfile,
        resetProfile: resetProfile,
        userProfile: user,
        token: getClaimsFromToken
    };
}]);

app.factory('Me', ['Auth', 'Restangular', '$q', function(Auth, Restangular, $q) {

    var initialized = false;
    var me = false;
    var createPost = false;
    var uid = false;

    var init = function() {
        //console.log("true");
        uid = Auth.token.user_id;
        me = Restangular.one('users', uid);
        //me.get();

        createPost = function(post, anon) {
            //console.log(anon);
            return me.post("posts", {
                "post": post,
                "anonymous": anon
            });
        };


        initialized = true;
    };

    var callInitPromiseOnLogin = function() {
        Auth.refreshProfile().then(init());
    };

    return {

        initialized: initialized,
        id: uid,
        me: me,
        callInit: callInitPromiseOnLogin,
        authenticated: Auth.userProfile.authenticated,
        profile: Auth.userProfile,
        createPost: createPost

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
