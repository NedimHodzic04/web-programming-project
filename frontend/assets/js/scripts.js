var app = $.spapp({
  defaultView: "#view_1",
  templateDir: "./tpl/",
  pageNotFound: "error_404"
});

// Initialize Spapp and trigger custom events
app.run();
