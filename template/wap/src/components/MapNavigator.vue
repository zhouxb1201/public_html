<template>
  <van-popup
    v-model="value"
    position="bottom"
    :close-on-click-overlay="false"
    @click-overlay="isShow(false)"
  >
    <van-cell-group class="cell-group">
      <van-cell
        clickable
        v-for="(item,index) in actions"
        :key="index"
        :value="item.name"
        value-class="text-center"
        @click="select(item)"
      />
    </van-cell-group>
  </van-popup>
</template>

<script>
import { encodeUriParams, txMapTransBMap, bMapTransTxMap } from "@/utils/util";
const defaultParams = {
  lng: "",
  lat: "",
  name: ""
};
export default {
  data() {
    return {
      actions: [
        {
          name: "高德地图",
          type: "gd"
        },
        {
          name: "百度地图",
          type: "bd"
        },
        {
          name: "腾讯地图",
          type: "tx"
        }
      ]
    };
  },
  props: {
    value: {
      type: Boolean,
      default: false
    },
    // 导航参数
    params: {
      type: Object,
      default: defaultParams
    }
  },
  computed: {
    location() {
      const { localCity, localInfo } = this.$store.state._store;
      return {
        lng: localInfo.location.lng,
        lat: localInfo.location.lat,
        city: localCity
      };
    }
  },
  methods: {
    isShow(flag) {
      this.$emit("input", flag);
    },
    select({ type }) {
      this.isShow(false);
      this.openMap(this[type + "Map"]());
    },
    openMap(url) {
      this.isShow(false);
      // this.$Dialog.alert({ message: JSON.stringify(url) });
      location.assign(url);
    },
    // 高德地图
    gdMap() {
      const location = bMapTransTxMap(this.location.lng, this.location.lat);
      const api = "https://uri.amap.com/navigation?";
      const params = this.params;
      const toLocation = bMapTransTxMap(params.lng, params.lat);
      const obj = {
        from: location.lng + "," + location.lat + "," + "当前位置",
        to: toLocation.lng + "," + toLocation.lat + "," + params.name,
        src: this.$store.state.domain,
        callnative: 1,
        via: "",
        mode: "",
        policy: "",
        coordinate: "gaode"
      };
      console.log(obj);
      return api + encodeUriParams(obj);
    },
    // 百度地图
    bdMap() {
      const location = this.location;
      const api = "http://api.map.baidu.com/direction?";
      const params = this.params;
      const obj = {
        origin: location.lat + "," + location.lng,
        destination: params.lat + "," + params.lng,
        mode: "driving",
        output: "html",
        region: location.city,
        src: this.$store.state.domain
      };
      console.log(obj);
      return api + encodeUriParams(obj);
    },
    // 腾讯地图
    txMap() {
      const location = bMapTransTxMap(this.location.lng, this.location.lat);
      const api = "https://apis.map.qq.com/uri/v1/routeplan?";
      const params = this.params;
      const toLocation = bMapTransTxMap(params.lng, params.lat);
      const obj = {
        type: "drive",
        fromcoord: location.lng + "," + location.lat,
        to: params.name,
        tocoord: toLocation.lng + "," + toLocation.lat,
        coord_type: 2,
        referer: this.$store.state.domain
      };
      console.log(obj);
      return api + encodeUriParams(obj);
    }
  },
  components: {}
};
</script>

<style scoped>
</style>