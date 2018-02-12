<template>
<div >
    <h1>This is my movies page</h1>
    <input type="text" v-model="search" placeholder="Search by title">
    <div class="movies container">
        <div v-for="movie in filteredMovies" :key="movie.title" class="single-movie">
            <p class="title">{{movie.title}}</p>
            <router-link :to="{ name: 'movie', params: { id: movie.nid } }" :key="movie.nid">
                <img v-bind:src="'http://drupal8vue.dev.loc' + movie.field_movie_poster" />
            </router-link>
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
        search: '',
      }
    },

    created() {
      this.getAllMovies();
    },

    computed: {
        filteredMovies: function() {
            return this.movies.filter((movie) => {
                return movie.title.match(this.search);
            });
        }
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

<style lang="scss" src="bulma" scoped>
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
input {
	border: solid 1px #e8e8e8;
	font-family: 'Roboto', sans-serif;
	padding: 10px 7px;
	margin-bottom: 15px;
	outline: none;
}
</style>
