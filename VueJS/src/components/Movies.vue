<template>
<div >
    <h1>This is my movies page</h1>
    <div class="movies">
        <div v-for="movie in movies" :key="movie.title" class="single-movie">
            <p><strong>Title:</strong><br> {{movie.title}}</p>
            <img v-bind:src="'http://drupal8vue.dev.loc' + movie.field_movie_poster" />
            <p><strong>Description : </strong></p>
            <p>{{movie.body}}</p>
            <router-link
            active-class="is-active"
            class="link"
            :to="{ name: 'movie', params: { id: movie.nid } }" :key="movie.title">
          Read More ...
        </router-link>
            <br>
        </div>
        
    </div>
</div>
</template>

<script>
import axios from 'axios'

export default {
    data () {
      return {
        movies: [],
        endpoint: 'http://drupal8vue.dev.loc/api/movies/',
      }
    },

    created() {
      this.getAllMovies();
    },

    methods: {
      getAllMovies() {
        axios.get(this.endpoint)
          .then(response => {
            this.movies = response.data;
          })
          .catch(error => {
            console.log('-----error-------');
            console.log(error);
          })
      }
    }
  }
</script>

<style scoped>
.movies {
    display: grid;
    grid-template-columns: repeat(3,1fr);
    grid-column-gap: 20px;
}
.single-movie {
    background: rgba(233, 213,87, 0.3);
    padding: 10px;
    margin: 10px;
}
</style>
