<template>
  <div id="contact-form" class="contact-form container">
		<h1 class="contact-form_title">Contact Form</h1>
		<div class="separator"></div>

		<form id="contact_form"  v-on:submit.prevent="sendMessage" action="">
            <div class="field">
                <label class="label" for="name">Name:</label>
                <input  class="input" v-model="name" name="name" type="text" />
            </div>
            <div class="field">
                <label class="label" for="mail">Email</label>
                <input  class="input" v-model="mail" name="mail" type="mail" />
            </div>
			<div class="field">
                <label class="label" for="subject">Subject</label>
                <input  class="input" v-model="subject" name="subject" type="mail" />
            </div>
            <div class="field">
                <label class="label" for="message">Message</label>
                <textarea class="textarea" v-model="message" name="message"></textarea>
            </div>
            <button type="submit" class="button">Send</button>
</form>
	</div>

</template>

<script>
import axios from 'axios'
import VueResource from 'vue-resource'



export default {
    
    data() {
        return {
			endpoint: 'http://drupal8vue.dev.loc/contact_message?_format=json',
			name: 'Nemanja Bacic',
			mail: 'example@example.com',
			subject: 'Da vidimo dali vueJS radi',
			message: 'Lorem ipsum dolor sit amet'
		}
	},
	methods: {
      	sendMessage() {
			axios.post(this.endpoint, {
				credentials: "same-origin",
				headers: {
					'Authorization': 'Basic YWRtaW46YWRtaW4=',
					'Content-Type': 'application/hal+json'
					},
				"contact_form":[{"target_id":"feedback"}],
				"name":[{"value":this.name}],
				"mail":[{"value":this.mail}],
				"subject":[{"value":this.subject}],
				"message":[{"value":this.message}],
		}).then(response => { 
				console.log('Message sent !')
			})
			.catch(error => {
				console.log(error.response)
			});
		}
	}
}
</script>

<style lang="scss" src="bulma" scoped>
body {
	background: #f1f1f1;
	font-family: 'Roboto', sans-serif;
}

.contact-form {
	font-family: 16px;
	margin: 0 auto;
	max-width: 600px;
	width: 100%;
}

.contact-form .separator {
	border-bottom: solid 1px #ccc;
	margin-bottom: 15px;
}

.contact-form .form {
	display: flex;
	flex-direction: column;
	font-size: 16px;
}

.contact-form_title {
	color: #333;
	text-align: left;
	font-size: 28px;
}

.contact-form input[type="email"],
.contact-form input[type="text"],
.contact-form textarea {
	border: solid 1px #e8e8e8;
	font-family: 'Roboto', sans-serif;
	padding: 10px 7px;
	margin-bottom: 15px;
	outline: none;
}

.contact-form textarea {
	resize: none;
}

.contact-form .button {
	background: #da552f;
	border: solid 1px #da552f;
	color: white;
	cursor: pointer;
	padding: 10px 50px;
	text-align: center;
	text-transform: uppercase;
}

.contact-form .button:hover {
	background: #ea532a;
	border: solid 1px #ea532a;
}

.contact-form input[type="email"],
.contact-form input[type="text"],
.contact-form textarea,
.contact-form .button {
	font-size: 15px;
	border-radius: 3px
}
</style>
            