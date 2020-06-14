<template>
  <div class="store-search bg-f8">
    <Navbar />
    <TabSortScreen replace :set-params="setParams" />
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{pageType:'shop',message:'没有相关门店'}"
      @load="loadList"
    >
      <ListItem
        :items="item"
        v-for="(item, index) in list"
        :key="index"
        @click="onNavigation(index)"
      />
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import ListItem from "./component/ListItem";
import TabSortScreen from "./component/TabSortScreen";
import { GET_STORELIST } from "@/api/store";
import { list } from "@/mixins";
import { encodeUriParams } from "@/utils/util";
export default sfc({
  name: "store-search",
  data() {
    return {
      show: false,
      openType: null,
      index: null,
      actions: [
        // {
        //   name: "高德地图",
        //   type: "gd"
        // }
        {
          name: "百度地图",
          type: "bd"
        }
        // {
        //   name: "腾讯地图",
        //   type: "tx"
        // }
      ],

      params: {
        order: "distance",
        sort: "ASC",
        lng: "",
        lat: "",
        search_text: this.$route.query.search_text || ""
      }
    };
  },
  mixins: [list],
  mounted() {
    this.$store
      .dispatch("getBMapLocation")
      .then(({ location, address }) => {
        this.params.lng = location.lng;
        this.params.lat = location.lat;
        this.city = address.city;
        this.loadList();
      })
      .catch(error => {
        this.$Toast({ message: error + "将显示所有门店", duration: 3000 });
        this.$store.dispatch("getCurrentCityName").then(city => {
          this.city = city;
        });
        this.loadList();
      });
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
    },
    onSelect({ type }) {
      this.openType = type;
      this.show = false;
      this.openMap(this[type + "Map"]());
    },
    onNavigation(index) {
      this.index = index;
      // if (!this.openType) return (this.show = true);
      // this.openMap(this[this.openType + "Map"]());
      this.openMap(this.bdMap());
    },
    openMap(url) {
      // this.show = false;
      // this.$Dialog.alert({ message: JSON.stringify(url) });
      location.assign(url);
    },
    gdMap(index) {
      const { lng, lat } = this.params;
      const api = "https://uri.amap.com/navigation?";
      const items = this.list[this.index];
      const params = {
        from: lng + "," + lat + "," + "当前位置",
        to: items.lng + "," + items.lat + "," + items.store_name,
        src: this.$store.state.domain,
        callnative: 1
      };
      return api + encodeUriParams(params);
    },
    bdMap(index) {
      const { lng, lat } = this.params;
      const api = "http://api.map.baidu.com/direction?";
      const items = this.list[this.index];
      const params = {
        origin: lat + "," + lng,
        destination: items.lat + "," + items.lng,
        mode: "driving",
        output: "html",
        region: this.city,
        src: this.$store.state.domain
      };
      return api + encodeUriParams(params);
    },
    txMap(index) {
      const { lng, lat } = this.params;
      const api = "https://apis.map.qq.com/uri/v1/routeplan?";
      const items = this.list[this.index];
      const params = {
        type: "drive",
        fromcoord: lat + "," + lng,
        to: items.store_name,
        tocoord: items.lat + "," + items.lng,
        coord_type: 1,
        referer: this.$store.state.domain
      };
      return api + encodeUriParams(params);
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

