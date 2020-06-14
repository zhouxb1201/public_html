<template>
  <layout ref="load" class="store-home bg-f8">
    <HomeHeader :info="info" />
    <MainView :info="info" />
  </layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_STOREINFO } from "@/api/store";
import HomeHeader from "./component/home/Header";
import MainView from "./component/home/MainView";
export default sfc({
  name: "store-home",
  data() {
    return {
      info: {},
      params: {
        lng: "",
        lat: "",
        store_id: this.$route.params.id || ""
      }
    };
  },
  mounted() {
    if (this.$store.state.config.addons.store) {
      this.$store
        .dispatch("getBMapLocation")
        .then(({ location, address }) => {
          this.params.lng = location.lng;
          this.params.lat = location.lat;
          this.city = address.city;
          this.loadData();
        })
        .catch(error => {
          this.$Toast({ message: error + "将显示所有门店", duration: 3000 });
          this.$store.dispatch("getCurrentCityName").then(city => {
            this.city = city;
            this.loadData();
          });
        });
    } else {
      this.$refs.load.fail({ errorText: "未开启O2O应用", showFoot: false });
    }
  },
  methods: {
    loadData() {
      GET_STOREINFO(this.params)
        .then(({ data }) => {
          this.info = data;
          this.$refs.load.success();
        })
        .catch(() => {});
    }
  },
  beforeDestroy() {
    var iframes = document.getElementsByTagName("iframe")[0];
    iframes && iframes.remove();
  },
  components: {
    HomeHeader,
    MainView
  }
});
</script>

<style scoped>
</style>

