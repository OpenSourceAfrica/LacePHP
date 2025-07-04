/*-------------------------------------------------------------------------------------
Template: Knowledge Doc - The complete documentation template
Version:    1.0
Author: Vedhathemes
-------------------------------------------------------------------------------------*/
/*
  ================================================
  [  Table of contents  ]
  ================================================
  
  :: Preloader
  :: Sidebar Dropdown
  :: Sidebar Close and Show
  :: Chat box
  :: palette


  */

jQuery(function ($) {
  'use strict';

  /*-----------------------
      preloader
      -----------------------*/
     $(window).on("ready", function() {
         $('.preloader').fadeOut();
      });
      // end of preloader

  /*----------------------------
    sidebar dropdown
    ----------------------------*/
  $(".sidebar-dropdown > a").on("click", function () {
    $(".sidebar-submenu").slideUp(200);
    if (
      $(this)
      .parent()
      .hasClass("active")
    ) {
      $(".sidebar-dropdown").removeClass("active");
      $(this)
        .parent()
        .removeClass("active");
    } else {
      $(".sidebar-dropdown").removeClass("active");
      $(this)
        .next(".sidebar-submenu")
        .slideDown(200);
      $(this)
        .parent()
        .addClass("active");
    }
  });

  /*----------------------------
    sidebar close and show
    ----------------------------*/
  $("#close-sidebar").on("click", function () {
    $(".page-wrapper").toggleClass("toggled");
  });
  $("#show-sidebar").on("click", function () {
    $(".page-wrapper").toggleClass("toggled");
  });

  // navbar 
  window.slide = new SlideNav({
    changeHash: true
  });


  /*----------------------------
    chat box
    ----------------------------*/
  $(function () {
    var INDEX = 0;
    $("#chat-submit").on("click", function (e) {
      e.preventDefault();
      var msg = $("#chat-input").val();
      if (msg.trim() == '') {
        return false;
      }
      generate_message(msg, 'self');
      var buttons = [{
          name: 'Existing User',
          value: 'existing'
        },
        {
          name: 'New User',
          value: 'new'
        }
      ];
      setTimeout(function () {
        generate_message(msg, 'user');
      }, 1000)

    })

    function generate_message(msg, type) {
      INDEX++;
      var str = "";
      str += "<div id='cm-msg-" + INDEX + "' class=\"chat-msg " + type + "\">";
      str += "          <span class=\"msg-avatar\">";
      str += "            <img src=\"assets\/images\/chat-avatar.png\">";
      str += "          <\/span>";
      str += "          <div class=\"cm-msg-text\">";
      str += msg;
      str += "          <\/div>";
      str += "        <\/div>";
      $(".chat-logs").append(str);
      $("#cm-msg-" + INDEX).hide().fadeIn(300);
      if (type == 'self') {
        $("#chat-input").val('');
      }
      $(".chat-logs").stop().animate({
        scrollTop: $(".chat-logs")[0].scrollHeight
      }, 1000);
    }

    $("#chat-circle").on("click", function () {
      $("#chat-circle").toggle('scale');
      $(".chat-box").toggle('scale');
    })

    $(".chat-box-toggle").on("click", function () {
      $("#chat-circle").toggle('scale');
      $(".chat-box").toggle('scale');
    })

  })

  /*----------------------------
    palette
    ----------------------------*/
  $("#color-bar").on("click", function () {
    $(".color-palette").toggleClass('open-plt');
  });
  // end of palet

  /*color selection*/
  $('.color-palette li').on("click", function (e) {
    e.preventDefault();
    var stylesheet = 'color_0' + (jQuery(this).index() + 1) + '.css';
    jQuery('link#theme').attr('href', 'assets/css/' + stylesheet);
    jQuery('link#theme').on("load", function () {
      jQuery('link#main').attr('href', 'assets/css/' + stylesheet);
    });
  });
  // end of color selection
});