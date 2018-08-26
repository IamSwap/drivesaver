/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

if (window.Vue === undefined) {
    window.Vue = require("vue");
    window.Bus = new Vue();
}

window.swal = require('sweetalert');

import VueRouter from "vue-router";
Vue.use(VueRouter);

require("./core");

// Filters
require("./partials/filters");

import routes from "./routes";

const router = new VueRouter({
    mode: "history",
    linkActiveClass: "active",
    routes
});

const app = new Vue({
    el: "#app",
    router,
    data: {
        loading: false
    },
    created() {
        Bus.$on("showLoader", () => {
            this.loading = true;
        });
        Bus.$on("hideLoader", () => {
            this.loading = false;
        });
    }
});
