<template>
<div >
    <h1>This is my movies page</h1>
    <div class="movies">
        <div v-for="movie in movies" :key="movie.title" class="single-movie">
            <p><strong>Title:</strong><br> {{movie.title}}</p>
            <img v-bind:src="'http://drupal8vue.dev.loc' + movie.field_movie_poster" />
            <p><strong>Description : </strong></p>
            <p>{{movie.body}}</p>
            <br>
            <!-- <p><strong>Description : </strong><br>{{movie.body}}</p> -->
        </div>
    </div>
</div>
</template>

<script>

const url = "http://drupal8vue.dev.loc/api/movies";

export default {
    
    data: function() {
      return {
          movies: [],
      }
    },
    mounted() {
      fetch(url)
        .then(response => response.json())
        .then((data) => {
          this.movies = data;
        })
    },

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
