import Vue from 'vue';
import Router from 'vue-router';
import App from './components/App';
import MainPage from './components/MainPage';


Vue.use(Router);

const routes = [
    { path: '/', component: MainPage },
];

const router = new Router({
    routes,
});

const app = new Vue({
    router,
    render: h => h(App),
}).$mount('#app');
