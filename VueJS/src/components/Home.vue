<template>
  <div class="front">
      <aside>
        <h2>Lastest added movies</h2>
        <div v-for="item in items" :key="item.nid" class="single-movie">
        <img v-bind:src="'http://drupal8vue.dev.loc' + item.field_movie_poster" />
        <h3>{{item.title}}</h3>
        <p>{{item.body}}</p>
      </div>
      </aside>
      <main>
        
      </main>
      <aside>

      </aside>
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

<style lang="scss" scoped>
.front  {
  display: grid;
  grid-template-columns: 1fr 2fr 1fr;
  grid-column-gap: 20px;
  aside {
    background-color: red;
    .single-movie {
      padding: 30px;
      overflow: hidden;
      img {
        float: left;
        max-width: 100px;
        margin-right: 10px;
      }
      p {
        padding: 10px;
      }
    }
  }
  main {
    background-color: blue;
  }
}
</style>
