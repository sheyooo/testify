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

app.directive('appToolbarNoLogin', [function() {
    return {
        restrict: 'A',
        transclude: true,
        replace: true,
        templateUrl: 'partials/app-toolbar-no-login.html'
    };
}]);

app.directive('testifyPosts', ['PostService', function(PostService) {
    return {
        restrict: 'A',
        transclude: true,
        replace: true,
        templateUrl: 'templates/Post/posts.html',
        scope: {
            posts: '=testifyPosts',
        },
        controller: function($scope) {
            //console.log($scope.posts);

            this.SDeletePost = function(post_id) {
                PostService.post(post_id).remove().then(function(r) {
                    var i = $scope.posts.map(function(x) {
                            return x.id;
                        })
                        .indexOf(post_id);
                    //console.log($scope);
                    //array.splice(removeIndex, 1);
                    $scope.posts.splice(i, 1);
                    //scope.post = "";
                });

            };

        }
    };
}]);

app.directive('testifyPost', ['PostService', 'CommentService', 'Auth', 'UXService', 'Facebook', 'appUrl', function(PostService, CommentService, Auth, UXService, Facebook, appUrl) {
    return {
        restrict: 'A',
        require: "^?testifyPosts",
        scope: {
            post: '=testifyPost'
        },
        templateUrl: 'templates/Post/post.html',
        link: function(scope, element, attrs, SuperTPostsCtrl) {
            scope.user = Auth.userProfile;
            scope.CommentsUI = {
                loading: false
            };

            var actions = {
                'App\\Tap': ['tapped into', 'this post'],
                'App\\Favorite': ['favorited', 'this post'],
                'App\\Amen': ['said amen', 'to this post'],
                'App\\Comment': ['commented', 'on this post']
            };

            if (scope.post.user_ref_activities) {
                if (scope.post.user_ref_activities.length > 0) {
                    interpretation = '';
                    i = 0;
                    var ref_activities = scope.post.user_ref_activities;
                    l = scope.post.user_ref_activities.length;

                    if (l == 1) {
                        a_t = ref_activities[0].action_type;
                        interpretation += ' ' +
                            actions[a_t][0] +
                            ' ' + actions[a_t][1];
                    } else if (l == 2) {

                        for (var action in ref_activities) {
                            i++;
                            a_t = ref_activities[action].action_type;

                            if (i == l - 1) {
                                interpretation += ' ' + actions[a_t][0];
                            }

                            if (i == l) {
                                interpretation += ' and ' + actions[a_t][0] + ' ' + actions[a_t][1];
                            }
                        }
                    } else if (l > 2) {
                        for (var action in ref_activities) {
                            i++;
                            a_t = ref_activities[action].action_type;
                            if (i == l - (l - 1)) {
                                interpretation += ' ' + actions[a_t][0];
                            } else if (i <= l - 1) {
                                interpretation += ', ' + actions[a_t][0];
                            } else if (l >= i) {
                                interpretation += ' and ' + actions[a_t][0] + ' ' + actions[a_t][1];
                            }
                        }
                    }

                    scope.interpretation = interpretation;
                }

            }

            var originatorEv;
            scope.openMenu = function($mdOpenMenu, ev) {
                originatorEv = ev;
                $mdOpenMenu(ev);
                //console.log(ev)
            };

            scope.showCommentBox = false;
            scope.openCommentBox = function() {
                //console.log(scope.post.post_id);
                if (scope.showCommentBox === false) {
                    scope.CommentsUI.loading = true;
                    PostService.post(scope.post.id).getList('comments').then(function(r) {
                        //console.log(r);
                        scope.post.comments = r.data;
                        scope.CommentsUI.loading = false;
                    }, function(r) {
                        scope.CommentsUI.loading = false;
                    });
                }
                scope.showCommentBox = true;
            };

            scope.addComment = function(ev) {
                var doCommentPost = function() {
                    //console.log(Date());
                    if (scope.commentBox) {
                        PostService.post(scope.post.id).post('comments', {
                            text: scope.commentBox
                        }).then(function(r) {
                            //console.log(r);

                            //console.log(comment.text);
                            scope.post.comments_count++;
                            scope.post.comments.unshift(r.data);
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

            scope.deleteComment = function(comment_id) {
                var comment = CommentService.comment(comment_id);
                comment.remove().then(function(r) {
                    //console.log(r);
                    var i = scope.post.comments.map(function(x) {
                            return x.id;
                        })
                        .indexOf(comment_id);
                    //console.log($scope);
                    //array.splice(removeIndex, 1);
                    scope.post.comments.splice(i, 1);
                    scope.post.comments_count--;

                    //scope.post = "";

                });
            };

            scope.toggleFavorite = function(ev) {
                //console.log(scope.post.post_id)
                var doFavorite = function() {
                    if (scope.post.favorited) {
                        scope.post.favorited = false;
                        scope.post.favorites_count--;
                        PostService.post(scope.post.id).one('favorites').remove().then(function(r) {
                            //console.log(r);
                            scope.post.favorited = r.data.status;
                            scope.post.favorites_count = r.data.count;
                        });
                    } else {
                        scope.post.favorited = true;
                        scope.post.favorites_count++;
                        PostService.post(scope.post.id).one('favorites').post().then(function(r) {
                            //console.log("created");
                            scope.post.favorited = r.data.status;
                            scope.post.favorites_count = r.data.count;
                        });
                    }
                };
                if (Auth.userProfile.authenticated) {
                    doFavorite();
                } else {
                    UXService.signinModal(ev).then(function() {
                        doFavorite();
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
                        PostService.post(scope.post.id).one('taps').remove().then(function(r) {
                            //console.log(r);
                            scope.post.tapped_into = r.data.status;
                            scope.post.taps_count = r.data.count;
                        });
                    } else {
                        scope.post.tapped_into = true;
                        scope.post.taps_count++;
                        PostService.post(scope.post.id).one('taps').post().then(function(r) {
                            //console.log("created");
                            scope.post.tapped_into = r.data.status;
                            scope.post.taps_count = r.data.count;
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

            scope.sayAmen = function(ev) {
                //console.log(scope.post.post_id)
                var doSayAmen = function() {

                    scope.post.amen = true;
                    scope.post.amens_count++;
                    PostService.post(scope.post.id).one('amens').post().then(function(r) {
                        //console.log("created");
                        scope.post.amen = r.data.status;
                        scope.post.amens_count = r.data.count;
                    });

                };
                if (Auth.userProfile.authenticated) {
                    doSayAmen();
                } else {
                    UXService.signinModal(ev).then(function() {
                        doSayAmen();
                    });

                }
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
                //scope.post = null;
                SuperTPostsCtrl.SDeletePost(scope.post.id);

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

app.directive('testifyComposer', [function() {
    return {
        restrict: 'A',
        templateUrl: 'partials/testifyComposer.html',
        controller: function($scope, UXService, AppService, $mdToast, Me, Upload,
            apiBase, $timeout, $document, $q) {
            $scope.selectCategories = ['general'];
            $scope.files = [];
            $scope.newPost = {
                creating: false
            };
            var categories = [];

            AppService.getCategories.then(function(cats) {
                $scope.categories = cats.data;
                //console.log(tags);
            });

            $scope.selectCategories = function(evt) {
                UXService.filePostModal(evt);

                //uxmodal popup or angular ui popup
            };


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
                console.log('kk');
                $scope.files.splice(i, 1);
            };

            var uploadImages = function(files) {
                var d = $q.defer();
                var finished = [];

                $scope.files = files;

                var doUpload = function(file) {
                    file.file = file;
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
                        if (files.length == finished.length) {
                            d.resolve(finished);
                        }
                    }, function(evt) {
                        file.progress = Math.min(100, parseInt(100.0 *
                            evt.loaded / evt.total));
                    });
                };

                if (files && files.length) {
                    angular.forEach(files, function(file) {
                        doUpload(file);
                    });


                }
                return d.promise;

            };

            $scope.composePost = function(ev) {
                //post = "";
                //console.log($scope);
                post = $scope.post;
                anonymous = $scope.anonymous;

                if (!post) {
                    post = " ";
                }

                if (anonymous.$viewValue === false || anonymous === false) {
                    anonymous = 0;
                } else {
                    anonymous = 1;
                }

                var createPost = function(o) {
                    //console.log(Me.sendPost);
                    $scope.newPost.creating = true;
                    var cats = [];

                    angular.forEach($scope.chips, function(value, key) {
                        cats.push(value.id);
                    });


                    Me.sendPost({
                        post: o.p,
                        anonymous: o.a,
                        categories: cats,
                        images: o.i
                    }).then(function(r) {
                        $scope.newPost.creating = false;
                        $scope.post = "";
                        $scope.composingPost = false;
                        $scope.files = [];
                        $scope.chips = [];

                        //console.log(r);
                        //console.log($scope.posts);
                        if (r.status === 201) {
                            $scope.app.posts.unshift(r.data);
                            //console.log(r.data);
                        }
                    }, function(r) {
                        $scope.newPost.creating = false;
                    });
                };

                if ($scope.files.length) {
                    uploadImages($scope.files).then(
                        function(id_arr) {
                            //console.log(id_arr);
                            createPost({
                                p: post.trim(),
                                a: anonymous,
                                i: id_arr
                            });
                        });
                } else {
                    if (post.trim()) {
                        createPost({
                            p: post.trim(),
                            a: anonymous,
                            i: []
                        });
                    } else {
                        UXService.alert(ev, "Post can't be empty!");
                    }

                }
            };
        }
    };
}]);
