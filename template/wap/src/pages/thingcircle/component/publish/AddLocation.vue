<template>
  <div>
    <van-cell
      :title="title"
      class="nowrap"
      :value="text"
      @click="loadData"
      is-link
      :title-class="title != '添加定位' ? color.yes : color.no"
    >
      <van-icon
        slot="icon"
        :style="{color:title != '添加定位' ? '#6790db' : '#cccccc'}"
        class="cell-left-icon"
        name="location"
        size="18px"
      />
    </van-cell>
    <van-popup v-model="showList" position="bottom" class="location-popup">
      <HeadSearch
        :disabled="false"
        :placeholder="placeholder"
        showLeft
        show-action
        :leftClick="leftClick"
        @rightAction="getSearchInfo"
      />
      <div class="location-list">
        <van-cell title="不显示位置" @click="onAddPonit('','')">
          <div slot="icon" class="left-icon"></div>
        </van-cell>
        <van-cell
          :title="item.name"
          :label="item.address"
          v-for="(item,index) in address"
          :key="index"
          @click="onAddPonit(item.name,item.location)"
        />
      </div>
    </van-popup>
  </div>
</template>

<script>
import HeadSearch from "@/components/HeadSearch";
import { Search } from "vant";
export default {
  data() {
    return {
      showList: false,
      placeholder: "请输入搜索关键字",
      city: "",
      address: [],
      title: "添加定位",
      text: "让附近更多的人发现你",
      color: {
        yes: "cr-ff454e",
        no: "cr-323233"
      },
      htp: null
    };
  },
  methods: {
    loadData() {
      const $this = this;
      if ($this.$store.state.domain.substring(0, 5) == "https") {
        $this.htp = "https";
      } else {
        $this.htp = "http";
      }
      $this.showList = true;
      $this.$store
        .dispatch("getBMapLocation")
        .then(({ location, address }) => {
          $this.city = address.city;
          console.log(location);
          $this.getInitInfo(location);
        })
        .catch(error => {});
    },
    getInitInfo(location) {
      const $this = this;
      let loca = location.lat + "," + location.lng;
      let query = "公司企业$交通设施$教育培训$金融";
      let tag = "飞机场,火车站,地铁站,高等院校,中学,小学,幼儿园,公司,银行";
      const url =
        $this.htp +
        "://api.map.baidu.com/place/v2/search?query=" +
        query +
        "&tag=" +
        tag +
        "&location=" +
        loca +
        "&radius=3000" +
        $this.city +
        "&output=json&ak=t16W0CsDyfV8QjlSgS17lgsI&page_size=20";
      $this.$jsonp(url).then(data => {
        $this.address = data.results;
      });
    },
    leftClick() {
      this.showList = false;
    },
    getSearchInfo(value) {
      const $this = this;
      const url =
        $this.htp +
        "://api.map.baidu.com/place/v2/search?query=" +
        value +
        "&region=" +
        $this.city +
        "&output=json&ak=t16W0CsDyfV8QjlSgS17lgsI&page_size=20";
      $this.$jsonp(url).then(data => {
        $this.address = data.results;
      });
    },
    onAddPonit(name, location) {
      this.text = name ? "" : "让附近更多的人发现你";
      this.title = name ? name : "添加定位";
      this.showList = false;
      let address = {
        name: name ? name : "",
        lat: location ? location.lat : "",
        lng: location ? location.lng : ""
      };
      this.$emit("add-location", address);
    }
  },
  components: {
    [Search.name]: Search,
    HeadSearch
  }
};
</script>

<style scoped>
.location-popup {
  height: 100%;
  border-radius: 0;
}
.nowrap >>> .van-cell__value {
  text-overflow: ellipsis;
  white-space: nowrap;
}
.location-list {
  height: 100%;
  box-sizing: border-box;
  max-height: calc(100vh - 48px);
  overflow-y: auto;
}
.cr-323233 {
  color: #323233;
}
.cr-ff454e {
  color: #6790db;
}
.left-icon {
  width: 15px;
  height: 15px;
  border-radius: 50%;
  border: 1px solid #494848;
  margin-top: 4px;
  position: relative;
  margin-right: 5px;
}
.left-icon::before {
  content: "";
  width: 100%;
  height: 1px;
  background-color: #494848;
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%) rotate(45deg);
}
.cell-left-icon {
  min-width: 1em;
  height: 24px;
  line-height: 24px;
  margin-right: 5px;
  border-radius: 50%;
  color: #cccccc;
}
</style>