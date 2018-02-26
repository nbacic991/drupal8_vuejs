<template>
  <div>
      <h2>Create New Movie</h2>
  </div>
</template>

<script>
import axios from 'axios'

export default {
    data() {
        return {

        }
    },
    ready() {
        this.createTheMovie();
    },
    methods: {
        createTheMovie() {
            let data = {
                '_links': {
                    'type': {
                        'href' : 'http://drupal8vue.dev.loc/rest/type/node/movies'
                    },
                },
                'title': [
                    {
                        'value' : 'Our New Movie'
                    }
                ],
                'body': [
                    {
                        'value': 'Description of the movie.'
                    }
                ]
            }
            axios.post('http://drupal8vue.dev.loc/entity/node?_format=json',data);
        }
    }
}
</script>

<style lang="scss">

</style>
