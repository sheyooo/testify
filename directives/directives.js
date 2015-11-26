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

app.directive('searchbox', [function() {
    return {
        restrict: 'A',
        transclude: true,
        replace: true,
        templateUrl: 'partials/searchbox.html'
    };
}]);

app.directive('appToolbar', [function() {
    return {
        restrict: 'A',
        transclude: true,
        replace: true,
        templateUrl: 'partials/app-toolbar.html'
    };
}]);

app.directive('testifyPosts', ['PostService', function(PostService) {
    return {
        restrict: 'A',
        transclude: true,
        replace: true,
        templateUrl: 'templates/Post/posts.html',
        controller: function($scope) {
            this.SDeletePost = function(post_id) {
                PostService.post(post_id).remove().then(function(r) {
                    var i = $scope.posts.map(function(x) {
                            return x.post_id;
                        })
                        .indexOf(post_id);
                    //array.splice(removeIndex, 1);
                    $scope.posts.splice(i, 1);
                    //scope.post = "";
                });

            };

        }
    };
}]);

app.directive('testifyPost', ['PostService', 'Auth', 'UXService', 'Facebook', 'appUrl', function(PostService, Auth, UXService, Facebook, appUrl) {
    return {
        restrict: 'A',
        require: "^?testifyPosts",
        scope: {
            testifyPost: '='
        },
        templateUrl: 'templates/Post/post.html',
        link: function(scope, element, attrs, SuperTPostsCtrl) {
            scope.user = Auth.userProfile;
            scope.post = scope.testifyPost;
            //scope.commentBox = 
            scope.CommentsUI = {
                loading: false
            };

            var originatorEv;
            scope.openPostMenu = function($mdOpenMenu, ev) {
                originatorEv = ev;
                $mdOpenMenu(ev);
                //console.log(ev)
            };

            scope.showCommentBox = false;
            scope.openCommentBox = function() {
                //console.log(scope.post.post_id);
                if (scope.showCommentBox === false) {
                    scope.CommentsUI.loading = true;
                    PostService.post(scope.post.post_id).getList('comments').then(function(r) {
                        //console.log(r);
                        scope.post.comments = r.data;
                        scope.CommentsUI.loading = false;

                    });
                }
                scope.showCommentBox = true;
            };

            scope.addComment = function(ev) {
                var doCommentPost = function() {
                    //console.log(Date());
                    if (scope.commentBox) {
                        PostService.post(scope.post.post_id).post('comments', {
                            text: scope.commentBox
                        }).then(function(r) {
                            //console.log(r);
                            var comment = {
                                comment_id: r.data.comment_id,
                                user_id: Auth.userProfile.user_id,
                                post_id: scope.post.post_id,
                                text: r.data.comment,
                                time: new Date(),
                                user: {
                                    user_id: Auth.userProfile.user_id,
                                    name: Auth.userProfile.name,
                                    avatar: Auth.userProfile.avatar
                                }
                            };
                            //console.log(comment.text);
                            scope.post.comments_count++;
                            scope.post.comments.push(comment);
                            scope.commentBox = "";

                        });
                    }
                };
                if (Auth.userProfile.authenticated) {
                    doCommentPost();
                } else {
                    UXService.signinModal(ev).then(function() {
                        doCommentPost();
                    }, function() {
                        v = "Unsuccessful login";
                    });
                }
            };

            scope.toggleLike = function(ev) {
                //console.log(scope.post.post_id)
                var doLike = function() {
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
                };
                if (Auth.userProfile.authenticated) {
                    doLike();
                } else {
                    UXService.signinModal(ev).then(function() {
                        doLike();
                    });

                }
                //console.log(scope.post);
            };

            scope.toggleTap = function(ev) {
                //console.log(scope.post.post_id)
                var doTap = function() {
                    if (scope.post.tapped_into) {
                        scope.post.tapped_into = false;
                        scope.post.taps_count--;
                        PostService.post(scope.post.post_id).one('taps').remove().then(function(r) {
                            //console.log(r);
                            scope.post.tapped_into = r.data.status;
                            scope.post.taps_count = r.data.taps;
                        });
                    } else {
                        scope.post.tapped_into = true;
                        scope.post.taps_count++;
                        PostService.post(scope.post.post_id).one('taps').post().then(function(r) {
                            //console.log("created");
                            scope.post.tapped_into = r.data.status;
                            scope.post.taps_count = r.data.taps;
                        });
                    }
                };

                if (Auth.userProfile.authenticated) {
                    doTap();
                } else {
                    UXService.signinModal(ev).then(function() {
                        doTap();
                    });
                }
                //console.log(scope.post);
            };

            scope.shareToFb = function() {
                Facebook.ui({
                    method: 'feed',
                    //link: 'https://testify-for-testimonies.herokuapp.com/api/fb-share/56',
                    link: appUrl,
                    picture: appUrl + '/img/testify-fb-share-pic.png',
                    name: 'Testify',
                    caption: 'Sharing God\'s goodness',
                    description: scope.post.text + ' (Tesfify is a community for sharing your testimonies and engaging with other people\'s testimonies)'
                }, function(response) {});
            };

            scope.deletePost = function() {
                SuperTPostsCtrl.SDeletePost(scope.post.post_id);

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
