<template>
  <Layout ref="load" class="blockchain-centre bg-f8">
    <Navbar :title="navbarTitle" />
    <CentreHeader :type="$route.params.type" />
    <EosResourceGroup v-if="$route.params.type == 'eos'" :type="$route.params.type" />
    <CentreChart :type="$route.params.type" />
    <CentreBtnGroup :type="$route.params.type" />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import CentreHeader from "./component/CentreHeader";
import EosResourceGroup from "./component/EosResourceGroup";
import CentreChart from "./component/CentreChart";
import CentreBtnGroup from "./component/CentreBtnGroup";
import blockchain from "./mixin";
export default sfc({
  name: "blockchain-centre",
  data() {
    return {
      info: {}
    };
  },
  mixins: [blockchain],
  computed: {
    navbarTitle() {
      let title = this.$route.params.type
        ? this.$route.params.type.toUpperCase()
        : "";
      if (title) document.title = title;
      return title;
    }
  },
  methods: {
    loadData(data) {
      this.info = data;
    }
  },
  components: {
    CentreHeader,
    EosResourceGroup,
    CentreChart,
    CentreBtnGroup
  }
});
</script>

<style scoped>
</style>
