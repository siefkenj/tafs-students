import Vue from "vue";
import VueRouter from "vue-router";
import App from "./App.vue";
// Components
import Redirect from "./views/redirect.vue";
import StudentSurveys from "./views/student-surveys.vue";
import StudentLandingPage from "./views/student-landing.vue";
import "material-design-icons-iconfont/dist/material-design-icons.css";
import "vuetify/dist/vuetify.min.css"; // Ensure you are using css-loader
import Vuetify from "vuetify";
// Helpers
Vue.use(Vuetify);
// Enabling routing
Vue.use(VueRouter);

// monkey-patch `fetch` to always include credentials.
// This is needed for Shibboleth integration
let originalFetch = fetch;
fetch = function(a, b) {
    return originalFetch(
        a,
        Object.assign(Object.assign({}, b), { credentials: "same-origin" })
    );
};

const routes = [
    { path: "/", component: Redirect },
    {
        path:
            "/user_id/:user_id/override_token/:override_token/student-landing",
        component: StudentLandingPage
    },
    {
        path: "/user_id/:user_id/override_token/:override_token/student",
        component: StudentSurveys
    }
];

const router = new VueRouter({
    routes: routes
});

new Vue({
    el: "#app",
    router: router,
    render: (h) => h(App)
});
