<template>
  <div class="store-list bg-f8">
    <Navbar />
    <TabSortScreen :set-params="setParams" />
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{pageType:'shop',message:'没有相关门店'}"
      @load="loadList"
    >
      <ListItem :items="item" v-for="(item, index) in list" :key="index" />
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import ListItem from "./component/ListItem";
import TabSortScreen from "./component/TabSortScreen";
import { GET_STORELIST } from "@/api/store";
import { list } from "@/mixins";
export default sfc({
  name: "store-list",
  data() {
    return {
      params: {
        order: "distance",
        sort: "ASC",
        lng: "",
        lat: "",
        search_text: ""
      }
    };
  },
  mixins: [list],
  mounted() {
    if (this.$store.state.config.addons.store) {
      this.$store
        .dispatch("getBMapLocation")
        .then(({ location, address }) => {
          this.params.lng = location.lng;
          this.params.lat = location.lat;
          this.loadList();
        })
        .catch(error => {
          this.$Toast({ message: error + "将显示所有门店", duration: 3000 });
          this.$store.dispatch("getCurrentCityName");
          this.loadList();
        });
    } else {
      this.$refs.load.fail({ errorText: "未开启O2O应用", showFoot: false });
    }
  },
  methods: {
    setParams(params) {
      this.params = params;
      this.loadList("init");
    },
    loadList(init) {
      const $this = this;
      let isShop = false;
      if (init && init === "init") {
        $this.initList();
      }
      if ($this.$route.query.shop_id) {
        $this.params.shop_id = $this.$route.query.shop_id;
        isShop = true;
      }
      GET_STORELIST($this.params, isShop)
        .then(({ data }) => {
          let list = data.store_list ? data.store_list : [];
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  },
  beforeDestroy() {
    var iframes = document.getElementsByTagName("iframe")[0];
    iframes && iframes.remove();
  },
  components: {
    ListItem,
    TabSortScreen
  }
});
</script>

<style scoped>
.cell-address {
  padding: 0;
  color: #909399;
  font-size: 12px;
}
</style>

