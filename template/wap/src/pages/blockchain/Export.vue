<template>
  <layout ref="load" class="blockchain-export bg-f8">
    <Navbar :title="navbarTitle" />
    <ExportGroup :type="type" :key-type="key" />
  </layout>
</template>

<script>
import sfc from "@/utils/create";
import ExportGroup from "./component/ExportGroup";
import blockchain from "./mixin";
export default sfc({
  name: "blockchain-export",
  data() {
    return {
      type: "",
      key: ""
    };
  },
  mixins: [blockchain],
  computed: {
    navbarTitle() {
      let key = "";
      if (this.$route.params.key == "keystore") key = "KeyStore";
      if (this.$route.params.key == "privatekey") key = "私钥";
      let title = "导出" + key;
      if (title) document.title = title;
      return title;
    }
  },
  methods: {
    loadData(data) {
      this.type = this.$route.params.type;
      this.key = this.$route.params.key;
    }
  },
  beforeDestroy() {
    this.$store.commit("removeBlockchainExportKey", {
      type: this.type,
      key: this.key
    });
  },
  deactivated() {
    this.$store.commit("removeBlockchainExportKey", {
      type: this.type,
      key: this.key
    });
  },
  components: {
    ExportGroup
  }
});
</script>

<style scoped>
</style>
