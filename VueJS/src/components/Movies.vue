<template>
<div class="container">
    <div class="control">
        <input class="input" type="text" v-model="search" placeholder="Search by title">
    </div>
    <div class="movies">
        <div v-for="movie in movies" :key="movie.title" class="single-movie">
            <p class="title">{{movie.title[0].value}}</p>
            <router-link :to="{ name: 'movie', params: { id: movie.nid[0].value } }" :key="movie.nid[0].value">
                <img v-bind:src="movie.field_movie_poster[0].url" />
                <br>
                <button class="btn">Read More ...</button>
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
        endpoint: 'http://drupal8vue.dev.loc/api/movies-test/',
        search: '',
      }
    },

    created() {
      this.getAllMovies();
    },

    computed: {

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

.btn {
    border-radius: 5px;
    color: #e8e8e8;
    background-color: red;
    border: 0;
    padding: 10px;
    font-weight: 700;
    font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
    &:hover {
        background-color: antiquewhite;
        -moz-transition: 300ms;
        -o-transition: 300ms;
        transition: 300ms;
        color: #469953;
    }

}
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
