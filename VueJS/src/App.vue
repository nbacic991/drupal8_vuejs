<template>
  <div id="app">
    <Navigation />
    <router-view/> 
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
