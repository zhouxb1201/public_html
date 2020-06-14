<template>
  <PopupBottom v-model="show" full-screen show-foot-close>
    <van-tabs v-model="active" class="tab-box">
      <van-tab v-for="(tab, t) in tabs" :title="tab.name" :key="t">
        <van-address-list
          v-show="tab.type == 'express'"
          class="list address-list"
          v-model="addressId"
          :list="addressList"
          @select="addressSelect"
        />
        <van-radio-group
          v-model="storeId"
          class="list store-list"
          v-show="tab.type == 'pickup'"
        >
          <van-cell-group :border="false">
            <van-cell
              clickable
              v-for="(item, s) in storeList"
              :key="s"
              @click="storeSelect(item)"
            >
              <van-radio :name="item.store_id" class="item">
                <div class="info">
                  <div class="name">
                    <van-col span="18">{{ item.store_name }}</van-col>
                    <van-col span="6" class="distance">
                      {{ item.distance | distance }}
                    </van-col>
                  </div>
                  <div class="detail">
                    <van-col span="24">{{ item.address }}</van-col>
                  </div>
                </div>
              </van-radio>
            </van-cell>
          </van-cell-group>
        </van-radio-group>
      </van-tab>
    </van-tabs>
  </PopupBottom>
</template>

<script>
import PopupBottom from "@/components/PopupBottom";
import { AddressList } from "vant";
import { GET_ADDRESSLIST } from "@/api/address";
export default {
  data() {
    return {
      active: 0,
      addressList: [],
      storeList: [],
      addressId: null,
      storeId: null
    };
  },
  watch: {
    value(e) {
      if (e && !this.addressList.length) {
        if (this.params.has_express != "" && this.params.has_store == 1) {
          this.getStoreList();
        } else if (this.params.has_express == "") {
          this.getAddressList();
        }
      }
    },
    active(e) {
      if (e == 1 && !this.storeList.length) {
        this.getStoreList();
      }
    }
  },
  props: {
    value: Boolean,
    params: Object
  },
  filters: {
    distance(value) {
      return value + "km";
    }
  },
  computed: {
    show: {
      get() {
        return this.value;
      },
      set(e) {
        this.$emit("input", e);
      }
    },
    tabs() {
      let tabs = [];
      if (this.params.has_express == "") {
        tabs.push({
          name: "线上配送",
          type: "express"
        });
      }
      if (this.params.has_store == 1) {
        if (this.$store.state.config.addons.store) {
          tabs.push({
            name: "门店自提",
            type: "pickup"
          });
        }
      }

      return tabs;
    }
  },
  methods: {
    addressSelect(e) {
      this.storeId = null;
      this.select({
        type: "express",
        id: e.id,
        address: e.address
      });
    },
    storeSelect(e) {
      this.addressId = null;
      this.select({
        type: "pickup",
        id: e.store_id,
        name: e.store_name,
        address: e.address
      });
    },
    select(item) {
      this.$emit("select", item);
    },
    getAddressList() {
      let list = [];
      GET_ADDRESSLIST({
        page_index: 1,
        page_size: 20
      }).then(({ data }) => {
        const arr = data.address_list || [];
        arr.forEach(e => {
          let obj = {};
          obj.id = e.id;
          obj.name = e.consigner;
          obj.tel = e.mobile;
          obj.address =
            e.province_name + e.city_name + e.district_name + e.address;
          obj.province = e.province;
          obj.city = e.city;
          obj.district = e.district;
          obj.area_code = e.area_code;
          list.push(obj);
        });
        this.addressList = list;
      });
    },
    getStoreList() {
      this.getLocation().then(location => {
        let params = {
          lng: location.lng || "",
          lat: location.lat || "",
          goods_id: this.$route.params.goodsid
        };
        this.$store.dispatch("getShopStoreList", params).then(data => {
          this.storeList = data || [];
          this.storeList.forEach(e => {
            e.address =
              e.province_name + e.city_name + e.dictrict_name + e.address;
          });
        });
      });
    },
    getLocation() {
      return new Promise((resolve, reject) => {
        this.$store
          .dispatch("getBMapLocation")
          .then(({ location }) => {
            resolve(location);
          })
          .catch(error => {
            this.$Toast(error);
            resolve({});
          });
      });
    }
  },
  components: {
    PopupBottom,
    [AddressList.name]: AddressList
  }
};
</script>

<style scoped>
.list {
  height: calc(100vh - 44px);
  padding-bottom: 0;
  overflow-y: auto;
}

.address-list >>> .van-address-item__edit {
  display: none;
}

.address-list >>> .van-address-list__add {
  display: none;
}

.store-list .item {
  display: flex;
  align-items: center;
  padding: 10px 0;
}

.store-list .item >>> .van-radio__label {
  flex: 1;
}

.store-list .distance {
  text-align: right;
  white-space: nowrap;
  color: #ff454e;
  font-size: 12px;
}

.store-list .detail {
  padding: 0;
  color: #909399;
  font-size: 12px;
}
</style>
