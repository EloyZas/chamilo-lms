<template>
  <MessageForm
    ref="createForm"
    :errors="violations"
    :values="item"
  >
    <div
      v-if="item.originalSender"
      class="field space-x-4"
    >
      <span v-t="'To'" />
      <MessageCommunicationParty
        :username="item.originalSender.username"
        :full-name="item.originalSender.fullName"
        :profile-image-url="item.originalSender.illustrationUrl"
      />
    </div>

    <div
      v-if="item.receiversCc"
      class="field space-x-4"
    >
      <span v-t="'Cc'" />
      <MessageCommunicationParty
        v-for="receiver in item.receiversCc"
        :key="receiver.receiver.id"
        :username="receiver.receiver.username"
        :full-name="receiver.receiver.fullName"
        :profile-image-url="receiver.receiver.illustrationUrl"
      />
    </div>

    <BaseTinyEditor
      v-model="item.content"
      :full-page="false"
      editor-id="message"
      required
    />

    <BaseButton
      :label="t('Send')"
      icon="plus"
      type="primary"
      @click="onReplyMessageForm"
    />
  </MessageForm>
  <Loading :visible="isLoading" />
</template>

<script setup>
import { useStore } from "vuex"
import { computed, onMounted, ref } from "vue"
import MessageForm from "../../components/message/Form.vue"
import Loading from "../../components/Loading.vue"
import isEmpty from "lodash/isEmpty"
import { useRoute, useRouter } from "vue-router"
import { MESSAGE_TYPE_INBOX } from "../../components/message/constants"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import { useSecurityStore } from "../../store/securityStore"
import { useNotification } from "../../composables/notification"
import { formatDateTimeFromISO } from "../../utils/dates"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import MessageCommunicationParty from "./MessageCommunicationParty.vue"

const item = ref({})
const store = useStore()
const securityStore = useSecurityStore()
const route = useRoute()
const router = useRouter()

const { t } = useI18n()
const notification = useNotification()

let id = isEmpty(route.params.id) ? route.query.id : route.params.id

let replyAll = "1" === route.query.all

onMounted(async () => {
  const response = await store.dispatch("message/load", id)

  item.value = await response
  const originalUserInfo = await store.dispatch("user/load", "/api/users/" + item.value.sender.id)
  const originalSenderName = originalUserInfo.fullName
  const originalSenderEmail = originalUserInfo.email
  const formattedDate = formatDateTimeFromISO(item.value.sendDate)
  const translatedHeader = t("Email reply header", [
    formattedDate,
    originalSenderName,
    `<a href="mailto:${originalSenderEmail}">${originalSenderEmail}</a>`,
  ])
  delete item.value["@id"]
  delete item.value["id"]
  delete item.value["firstReceiver"]
  //delete item.value['receivers'];
  delete item.value["sendDate"]

  item.value["originalSender"] = item.value["sender"]
  // New sender.
  item.value["sender"] = securityStore.user["@id"]

  // Set new receivers, will be loaded by onSendMessageForm()
  if (replyAll) {
    item.value.receiversTo.forEach((user) => {
      // Dont' add original sender.
      if (item.value["originalSender"]["@id"] === user.receiver["@id"]) {
        return
      }
      // Dont' add the current user.
      if (securityStore.user["@id"] === user.receiver["@id"]) {
        return
      }
      item.value.receiversCc.push(user)
    })

    // Check that the original sender is not already in the Cc.
    item.value.receiversCc.forEach(function (user, index, obj) {
      if (item.value["originalSender"]["@id"] === user.receiver["@id"]) {
        obj.splice(index, 1)
      }
    })

    /*item.value.receiversTo.forEach(function (user, index, obj) {
      if (securityStore.user['@id'] === user.receiver['@id']) {
        obj.splice(index, 1);
      }
    });*/
  } else {
    item.value["receivers"] = []
    item.value["receiversTo"] = null
    item.value["receiversCc"] = null
    item.value["receivers"][0] = item.value["originalSender"]
  }

  /*console.log('-----------------------');
  console.log(item.value.receiversCc);
  if (item.value.receiversCc) {
    item.value.receiversCc.forEach(user => {
      console.log(user);
      // Send to inbox
      usersCc.value.push(user.receiver);
    });
  }*/

  // Set reply content.
  item.value.title = t("Re:") + " " + item.value.title
  item.value.content = `<br /><br /><hr /><blockquote>${translatedHeader}<hr />${item.value.content}</blockquote>`
})

const isLoading = computed(() => store.state.message.isLoading)
const violations = computed(() => store.state.message.violations)

const createForm = ref()

const onReplyMessageForm = async () => {
  let users = []
  users.push({
    receiver: createForm.value.v$.item.$model.originalSender["@id"],
    receiverType: 1,
  })
  if (createForm.value.v$.item.$model.receiversCc) {
    createForm.value.v$.item.$model.receiversCc.forEach((user) => {
      // Send to inbox
      users.push({ receiver: user.receiver["@id"], receiverType: 2 })
    })
  }
  createForm.value.v$.item.$model.sender = "/api/users/" + securityStore.user.id
  createForm.value.v$.item.$model.receiversTo = null
  createForm.value.v$.item.$model.receiversCc = null
  createForm.value.v$.item.$model.receivers = users
  createForm.value.v$.item.$model.msgType = MESSAGE_TYPE_INBOX

  try {
    await store.dispatch("message/create", createForm.value.v$.item.$model)

    notification.showSuccessNotification("Message sent")

    await router.push({
      name: "MessageList",
    })
  } catch (e) {
    notification.showErrorNotification(e)
  }
}
</script>
