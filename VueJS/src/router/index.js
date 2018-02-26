import Vue from 'vue'
import Router from 'vue-router'
import Home from '@/components/Home'
import Movies from '@/components/Movies'
import SingleMovie from '@/components/SingleMovie'
import Actors from '@/components/Actors'
import SingleActor from '@/components/SingleActor'
import Login from '@/components/Login'
import Contact from '@/components/Contact.vue'
import CreateMovie from '@/components/CreateMovie'
import DeleteMovie from '@/components/DeleteMovie'
import RegisterUser from '@/components/RegisterUser'


Vue.use(Router)

export default new Router({
  mode: 'history',
  routes: [
    {
      path: '/',
      name: 'Home',
      component: Home
    },
    {
      path: '/movies',
      name: 'Movies',
      component: Movies
    },
    {
      path: '/movies/:id',
      name: 'movie',
      component: SingleMovie,
      props: true,
    },
    {
      path: '/actors',
      name: 'actors',
      component: Actors,
    },
    {
      path: '/actors/:id',
      name: 'actor',
      component: SingleActor,
      props: true,
    },
    {
      path: '/login',
      component: Login
    },
    {
      path: '/contact',
      component: Contact
    },
    {
      path: '/create',
      component: CreateMovie
    },
    {
      path: '/delete/:id',
      name: 'delete',
      component: DeleteMovie,
      props: true
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterUser
    }
  ]
})
