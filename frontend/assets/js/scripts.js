/*!
* Start Bootstrap - Shop Homepage v5.0.6 (https://startbootstrap.com/template/shop-homepage)
* Copyright 2013-2023 Start Bootstrap
* Licensed under MIT (https://github.com/StartBootstrap/startbootstrap-shop-homepage/blob/master/LICENSE)
*/
// This file is intentionally blank
// Use this file to add JavaScript to your project

var app = $.spapp({
    defaultView  : "#view_1",
    templateDir  : "./tpl/",
    pageNotFound : "error_404"
  });
  
  app.route({
    view : "view_2",
    load : "view_2.html"
  });

  app.route({
    view : "view_1",
    load : "view_1.html",
    onCreate: function() {  },
    onReady: function() {  }
  });

  app.run();