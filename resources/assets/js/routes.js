export default [
    // User Dashboard
    {
        path: "/dashboard",
        component: require("./components/Dashboard.vue"),
        props: true
    },
    {
        path: "/dashboard/settings",
        component: require("./components/Settings.vue"),
        props: true
    },
    {
        path: "/dashboard/*",
        component: require("./components/404.vue")
    }
];
