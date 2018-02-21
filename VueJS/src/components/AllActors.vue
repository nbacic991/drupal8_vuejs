<template>
  <div>
      <h1>Actors list with JSONapi module</h1>
      <ul>
        <div v-for="(actor, key) in allActors.data" :key="key">
            <h2 v-html="actor.attributes.title"></h2>
            <p class="body__text" v-html="actor.attributes.body.value"></p>
            <img class="image" v-bind:src="actor.relationships.field_actor_image.links.related"
            v-bind:alt="actor.relationships.field_actor_image.data.meta.alt">
        </div>
      </ul>
  </div>
</template>

<script>
import axios from 'axios'
export default {
  
    data(){
        return {
            allActors: [],
            endpoint: 'http://drupal8vue.dev.loc/jsonapi/node/actors/'
        }
    },
    created() {

    },
    mounted(){
        axios.get(this.endpoint)
          .then(response => {
            this.allActors = response.data;
          })
          .catch(error => {
            console.log('-----error-------');
            console.log(error);
        })
    },
    computed: {
        
    },
    methods: {
        
    }

}
</script>

<style lang="scss">

</style>
