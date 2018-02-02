<template>
  <div id="app">
    <Navigation />
    <router-view/>
    
    <!-- <router-link to="/">Home</router-link>
    <router-link to="movies">Movies</router-link> -->
   
  </div>
</template>

<script>
import Home from './components/Home'
import Movies from './components/Movies'
import Navigation from './components/Navigation'

export default {
  name: 'App',
  components: {
    Home,
    Movies,
    Navigation    
  }
}
</script>

<style>
#app {
  font-family: 'Avenir', Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
  margin-top: 60px;
}
</style>
