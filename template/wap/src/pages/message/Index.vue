<template>
  <Layout ref="load" class="message-index">
    <Navbar />
    <CellColumn v-if="$store.state.config.addons.thingcircle" />
    <CellChatGroup />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import CellChatGroup from "./component/CellChatGroup";
import CellColumn from "./component/CellColumn";
export default sfc({
  name: "message-index",
  data() {
    return {};
  },
  mounted() {
    this.$refs.load.success();
    if(this.$store.state.config.addons.qlkefu){
      this.$store.dispatch("getChatList");
    }
  },
  methods: {},
  components: {
    CellChatGroup,
    CellColumn
  }
});
</script>
