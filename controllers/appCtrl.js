app.controller('AppCtrl', function($rootScope, $scope, $mdSidenav, $mdMedia, $location, $state, $q, AppService, Auth, Me, appBase) {
    $scope.user = Auth.userProfile;
    $scope.composingPost = false;

    var originatorEv;
    $scope.openMenu = function($mdOpenMenu, ev) {
        originatorEv = ev;
        $mdOpenMenu(ev);
        //console.log(ev)
    };

    $scope.logout = function() {
        Auth.logout();
        //Me.callInit();
        //console.log($scope.user);
    };

    $scope.redirect = function(state) {
        $state.go(state);
    };

    $scope.tags = AppService.getCategories.then(function(cats) {
        $scope.cats = cats.data;
        //console.log(tags);
    });


    $scope.ui = {
        showSearch: false,
        toggleNav: function(which) {
            $mdSidenav(which).toggle();
            //console.log(which);
        },
        toggleSearchBox: function() {
            $scope.ui.showSearch = !$scope.ui.showSearch;
        }
    };

    $scope.menu = [{
        link: '',
        state: 'home',
        title: 'Feeds',
        icon: 'message-text',
        click: ''
    }, {
        link: 'entrance',
        state: 'entrance',
        title: 'Friends',
        icon: 'account-multiple',
        click: ''
    }, {
        link: '',
        state: 'home',
        title: 'Messages',
        icon: 'message-text-outline',
        click: ''
    }];
    $scope.admin = [{
        link: 'profile',
        state: 'profile',
        title: 'Profile',
        icon: 'account',
        action: null
    }, {
        link: 'showListBottomSheet($event)',
        state: 'settings',
        title: 'Settings',
        icon: 'settings',
        action: null
    }];


    $scope.getSearchResultIcon = function(type) {
        //console.log(icon[type]);
        return icon[type];
    };

    $scope.searchIcons = {
        "tag": "pound",
        "user": "at"
    };



    $scope.searchRepo = function(query) {
        var d = $q.defer();
        AppService.search.getList({
            q: query
        }).then(function(response) {
            d.resolve(response.data.plain());
            //console.log(response.data.plain());
            //return result;
        }, function() {
            d.reject();
        });

        return d.promise;
    };

});
