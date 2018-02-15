import Vue from 'vue'
import Router from 'vue-router'
import Home from '@/components/Home'
import Movies from '@/components/Movies'
import SingleMovie from '@/components/SingleMovie'
import Contact from '@/components/Contact'
import Actors from '@/components/Actors'
import SingleActor from '@/components/SingleActor'

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
      path: '/contact',
      name: 'contact',
      component: Contact
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
    }
  ]
})
