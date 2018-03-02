<template>
<div class="container">
   
    <div class="control">
        <input class="input" type="text" v-model="search" placeholder="Search by title">
        <br>
        <router-link class="btn btn-success" to='/create'>Create new movie</router-link>
    </div>
    <div class="movies">
        <div v-for="movie in filteredMovies" :key="movie.nid[0].value / 2" class="single-movie">
            <p class="title">{{movie.title[0].value}}</p>
            <router-link :to="{ name: 'movie', params: { id: movie.nid[0].value } }">
                <!-- <img v-bind:src="movie.field_movie_poster[0].url" /> -->
                <br>
                <br>
                <button class="btn btn-primary">Read More ...</button>
            </router-link>
            <br>
            <br>
            <span v-for="(genre, index) in movie.field_genre" :key="index">
                {{genre.value}},
            </span>
            <div class="col-md-3">
                <router-link :to="{ name: 'delete', params: { id: movie.nid[0].value } }">
                    <button class="btn btn-danger">Delete Movie</button>
                </router-link>
            </div>
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
        endpoint: 'http://drupal8vue.dev.loc/api/movies-test/',
        search: '',
      }
    },

    created() {
      this.getAllMovies();
    },

    computed: {
        filteredMovies: function() {
            return this.movies.filter((movie) => {
                return movie.title[0].value.match(this.search);
            });
        },
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

<style lang="scss">

.movies {
    display: grid;
    grid-template-columns: repeat(3,1fr);
    grid-column-gap: 20px;
    .single-movie {
        background: rgba(233, 213,87, 0.6);
        padding: 10px;
        margin: 10px;
        border-radius: 10px;
    }
}
input {
	border: solid 1px #e8e8e8;
	font-family: 'Roboto', sans-serif;
	padding: 10px 7px;
	margin-bottom: 15px;
	outline: none;
}
</style>
