<template>
  <Layout ref="load" class="blockchain-resource bg-f8">
    <Navbar />
    <ResourceProgressGroup :info="info" />
    <HeadTab v-model="tab_active" :tabs="tabs" @tab-change="onTabChange" class="tab" />
    <ResourceSubmitGroup v-model="subimtType" :info="info" />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import ResourceProgressGroup from "./component/ResourceProgressGroup";
import HeadTab from "@/components/HeadTab";
import ResourceSubmitGroup from "./component/ResourceSubmitGroup";
import blockchain from "./mixin";
export default sfc({
  name: "blockchain-resource",
  data() {
    return {
      tab_active: 0,
      tabs: [
        { name: "抵押资源", type: "mortgage" },
        { name: "赎回资源", type: "redeem" }
      ],
      info: {},
      subimtType: "mortgage"
    };
  },
  mixins: [blockchain],
  created() {
    this.pageType = "eos";
  },
  methods: {
    loadData(data) {
      this.info = data;
    },
    onTabChange(e) {
      this.subimtType = this.tabs[e].type;
    }
  },
  components: {
    ResourceProgressGroup,
    HeadTab,
    ResourceSubmitGroup
  }
});
</script>

<style scoped>
.tab {
  margin-top: 10px;
}
</style>
