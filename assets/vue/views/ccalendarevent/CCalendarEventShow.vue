<template>
  <div>
    <Toolbar
      v-if="item"
      :handle-delete="del"
      :handle-edit="editHandler"
    >
    </Toolbar>

    <p
      v-if="item"
      class="text-lg"
    >
      {{ item["title"] }}
    </p>

    <Loading :visible="isLoading" />
  </div>
</template>

<script>
import { mapActions, mapGetters } from "vuex"
import { mapFields } from "vuex-map-fields"
import Loading from "../../components/Loading.vue"
import ShowMixin from "../../mixins/ShowMixin"
import Toolbar from "../../components/Toolbar.vue"
import { useSecurityStore } from "../../store/securityStore"
import { storeToRefs } from "pinia"

const servicePrefix = "CCalendarEvent"

export default {
  name: "CCalendarEventShow",
  components: {
    Loading,
    Toolbar,
  },
  mixins: [ShowMixin],
  setup() {
    const securityStore = useSecurityStore()

    const { isAuthenticated, isAdmin, isCurrentTeacher } = storeToRefs(securityStore)

    return {
      isAuthenticated,
      isAdmin,
      isCurrentTeacher,
    }
  },
  computed: {
    ...mapFields("ccalendarevent", {
      isLoading: "isLoading",
    }),
    ...mapGetters("ccalendarevent", ["find"]),
  },
  methods: {
    ...mapActions("ccalendarevent", {
      deleteItem: "del",
      reset: "resetShow",
      retrieve: "loadWithQuery",
    }),
  },
  servicePrefix,
}
</script>
