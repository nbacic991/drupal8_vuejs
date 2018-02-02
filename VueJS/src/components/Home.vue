<template>
  <div class="front">
      <h1>This is my homepage</h1>
      <div v-for="item in items" :key="item.body">
        <p>{{item.body}}</p>
        <!-- <img v-bind:src="'http://drupal8vue.dev.loc' + movie.field_image" /> -->
        <img v-bind:src="'http://drupal8vue.dev.loc' + item.field_movie_poster" />
      </div>
  </div>
</template>

<script>
const url = "http://drupal8vue.dev.loc/api/front-page";

export default {
    
    data: function() {
      return {
          items: [],
      }
    },
    mounted() {
      fetch(url)
        .then(response => response.json())
        .then((data) => {
          this.items = data;
        })
    },

}
</script>

<style>

</style>
