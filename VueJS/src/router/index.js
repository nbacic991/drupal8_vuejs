import Vue from 'vue'
import Router from 'vue-router'
import Home from '@/components/Home'
import Movies from '@/components/Movies'
import SingleMovie from '@/components/SingleMovie'
import CreateMovie from '@/components/CreateMovie'

Vue.use(Router)

export default new Router({
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
      path: '/create',
      name: 'Create Movie',
      component: CreateMovie
    }
  ]
})