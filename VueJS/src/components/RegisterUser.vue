<template>
  <div class="container">
      <h2>Register new user</h2>
      <div class="alert alert-success" v-if="success">
          Movie has been added
      </div>
      <form v-on:submit="registerUser"> 
          <div class="form-group">
              <label for="">Username:</label>
              <br>
              <input type="text" name="title" v-model="name" class="form-control">
          </div>
          <div class="form-group">
              <label for="">Email:</label>
              <br>
              <input type="email" v-model="email" name="" class="form-control">
          </div>
          <div class="form-group">
              <label for="">Password:</label>
              <br>
              <input type="password" v-model="password" name="" class="form-control">
          </div>
          <button type="submit" class="btn btn-success">Create User</button>
      </form>
  </div>
</template>

<script>
export default {
    data() {
        return {
            name: '',
            email: '',
            password: '',
            success: false
        }
    },
    http: {
        headers: {
            'Accept' : 'json',
            'Content-Type' : 'application/hal+json',
            'Authorization' : 'Basic YWRtaW46YWRtaW4='
        }
    },
    methods: {
        registerUser: function(e) {
            
            e.preventDefault();
            let data = JSON.stringify({
                _links : {
                    type: {
                        href : "http://drupal8vue.dev.loc/rest/type/user/user"
                    },
                },
                // type: {
                //     target_id: 'movies'
                // },
                name: {
                    value : this.name
                },
                mail: {
                    value: this.email
                },
                pass: {
                    value: this.password
                },
                status: {
                    value: 1
                }
            }) 
            this.$http.post('http://drupal8vue.dev.loc/entity/user', data);
            this.success = true;
            this.name = ''
            this.email = ''
            this.password = ''
            console.log('Created new user')
        }
    }
}
</script>

<style>

</style>
