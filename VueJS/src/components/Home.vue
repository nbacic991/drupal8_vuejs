<template>

  <div>
    <slider>
        <slider-item v-for="item in items" :key="item.nid">
          <div class="">
            <img v-bind:src="'http://drupal8vue.dev.loc' + item.field_movie_poster">
            <p>{{item.title}}</p>
            <router-link :to="{ name: 'movie', params: { id: item.nid } }" :key="item.nid">
          <button>Read More</button>
        </router-link>
          </div>
        </slider-item>
    </slider>
    <div class="front">
      <aside>
        <h2>Lastest added movies</h2>
        <div v-for="item in movies" :key="item.nid" class="single-movie">
        <img v-bind:src="'http://drupal8vue.dev.loc' + item.field_movie_poster" />
        <h3>{{item.title}}</h3>
        <p>{{item.body}}</p>
        <router-link :to="{ name: 'movie', params: { id: item.nid } }" :key="item.nid">
          <button>Read More</button>
        </router-link>
        </div>
        <router-link to="/movies">See more...</router-link>
      </aside>
    <main>
        
    </main>
    <aside>

    </aside>
  </div>
      
  </div>
</template>

<script>
const endpoint = "http://drupal8vue.dev.loc/api/front-page";
import { Slider, SliderItem } from 'vue-easy-slider'

export default {
    
    data: function() {
      return {
          items: [],
          movies: []
      }
    },
    mounted() {
      fetch(endpoint)
        .then(response => response.json())
        .then((data) => {
          this.items = data;
          this.movies = data.splice(0,5);
        })
    },
  components: {
    Slider,
    SliderItem
  }

}
</script>

<style lang="scss" scoped>
.slider {
  max-width: 100% !important;
  height: 800px !important;
  margin: 20px;
  img {
    width: 100%;
    height: 650px;
  }
}
/**
Basic setup 
*/
.fade-transition {
  transition: all 0.8s ease;
  overflow: hidden;
  visibility: visible;
  opacity: 1;
  position: absolute;
}
.fade-enter, .fade-leave {
  opacity: 0;
  visibility: hidden;
}
.front  {
  display: grid;
  grid-template-columns: 1fr 2fr 1fr;
  grid-column-gap: 20px;
  aside {
    border: 1px solid gray;
    h2 {
      border-bottom: 1px solid gray;
      width: 50%;
      margin: auto;
    }
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
    border: 1px solid gray;
    .slider {
      max-width: 300px;
      width: 100%;
    }
  }
}
</style>
