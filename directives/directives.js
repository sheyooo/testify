app.directive('leftSidenav', [function() {

    return {
        restrict: 'A',
        transclude: true,
        replace: true,
        templateUrl: 'partials/left-sidenav.html'
    };
}]);

app.directive('rightSidenav', [function() {
    return {
        restrict: 'A',
        transclude: true,
        replace: true,
        templateUrl: 'partials/right-sidenav.html'
    };
}]);

app.directive('testifyPosts', [function() {
    return {
        restrict: 'E',
        transclude: true,
        replace: true,
        templateUrl: 'templates/Post/posts.html'
    };
}]);

app.directive('testifyPost', ['PostService', 'Auth', function(PostService, Auth) {
    return {
        restrict: 'A',
        scope: {
            testifyPost: '=',
        },
        templateUrl: 'templates/Post/post.html',
        link: function(scope, element) {
            scope.user = Auth.userProfile;
            scope.post = scope.testifyPost;

            var originatorEv;
            scope.openPostMenu = function($mdOpenMenu, ev) {
                originatorEv = ev;
                $mdOpenMenu(ev);
                //console.log(ev)
            };

            scope.showCommentBox = false;
            scope.openCommentBox = function() {
                scope.showCommentBox = true;

                console.log(scope.post.post_id);

                PostService.post(scope.post.post_id).getList('comments').then(function(r) {
                    console.log(r);

                    scope.post.comments = r.data;


                });
            };

            scope.toggleLike = function() {
                //console.log(scope.post.post_id)
                if (scope.post.liked) {
                    scope.post.liked = false;
                    scope.post.likes_count--;
                    PostService.post(scope.post.post_id).one('likes').remove().then(function(r) {
                        //console.log(r);
                        scope.post.liked = r.data.status;
                        scope.post.likes_count = r.data.likes;
                    });

                } else {
                    scope.post.liked = true;
                    scope.post.likes_count++;
                    PostService.post(scope.post.post_id).one('likes').post().then(function(r) {
                        //console.log("created");
                        scope.post.liked = r.data.status;
                        scope.post.likes_count = r.data.likes;
                    });
                }
                //console.log(scope.post);
            };
        }
    };
}]);

app.directive('myIcon', ['$timeout', function($timeout) {
    return {
        restrict: 'E',
        scope: {
            icon: "@"
        },
        replace: true,
        link: function(scope, e, attrs) {
            //scope.icon = scope.icon;
        },
        template: "<i class='mdi mdi-{{icon}}'></i>"
    };
}]);
